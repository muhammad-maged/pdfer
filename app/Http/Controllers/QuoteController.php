<?php

namespace App\Http\Controllers;

use App\Services\PdfRenderer;
use App\Services\Quotation;
use App\Services\QuoteView;
use App\Services\RenderFailed;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class QuoteController
{
    public function __invoke(Request $request, PdfRenderer $renderer)
    {
        $key = $request->bearerToken();
        $pilot = is_string($key) && preg_match('/\Apdfer_[a-f0-9]{64}\z/', $key)
            ? DB::table('pilots')->where('key_hash', hash('sha256', $key))->where('active', true)->first()
            : null;

        if (!$pilot) {
            return $this->error('invalid_api_key', 401);
        }

        if (!$request->isJson()) {
            return $this->error('expected_json', 415);
        }

        if (strlen($request->getContent()) > 65536) {
            return $this->error('payload_too_large', 413);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if (!is_string($idempotencyKey) || !preg_match('/\A[A-Za-z0-9._-]{1,100}\z/', $idempotencyKey)) {
            return $this->error('invalid_idempotency_key', 400);
        }

        $quote = Quotation::validate($request->json()->all());
        $fingerprint = hash('sha256', json_encode($this->canonical($quote), JSON_THROW_ON_ERROR));
        $html = QuoteView::html($quote, logoBase64: $pilot->logo_base64);
        $period = now('UTC')->format('Y-m');
        $id = (string) Str::uuid();

        $decision = DB::transaction(function () use ($pilot, $period, $idempotencyKey, $fingerprint, $id) {
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';
            $usageRow = ['pilot_id' => $pilot->id, 'period' => $period];
            if ($isSqlite) {
                // Acquire the SQLite write lock before a read on PHP 8.2 and 8.3.
                DB::table('pilot_usage')->insertOrIgnore($usageRow);
            }

            $current = DB::table('pilots')->where('id', $pilot->id)->lockForUpdate()->first();
            if (!$current || !$current->active) {
                return ['error' => 'invalid_api_key', 'status' => 401];
            }

            if (!$isSqlite) {
                DB::table('pilot_usage')->insertOrIgnore($usageRow);
            }

            $existing = DB::table('renders')->where('pilot_id', $pilot->id)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return ['existing' => $existing];
            }

            $reserved = DB::table('pilot_usage')->where('pilot_id', $pilot->id)->where('period', $period)
                ->where('consumed', '<', $current->monthly_quota)->increment('consumed');
            if ($reserved !== 1) {
                return ['error' => 'quota_exhausted', 'status' => 429];
            }

            DB::table('renders')->insert([
                'id' => $id,
                'pilot_id' => $pilot->id,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $fingerprint,
                'state' => 'pending',
                'created_at' => now('UTC'),
            ]);

            return [];
        }, 3);

        if (isset($decision['error'])) {
            return $this->error($decision['error'], $decision['status']);
        }

        if (isset($decision['existing'])) {
            $existing = $decision['existing'];
            if (!hash_equals($existing->request_hash, $fingerprint)) {
                return $this->error('idempotency_key_conflict', 409, $existing->id);
            }

            if ($existing->state === 'pending' && Carbon::parse($existing->created_at, 'UTC')->lte(now('UTC')->subMinutes(2))) {
                return $this->error('render_outcome_uncertain', 503, $existing->id);
            }

            return match ($existing->state) {
                'completed' => $existing->pdf_base64 && Carbon::parse($existing->expires_at, 'UTC')->isFuture()
                    ? $this->pdf(base64_decode($existing->pdf_base64, true), $quote['reference'], $existing->id)
                    : $this->error('render_expired', 410, $existing->id),
                'uncertain' => $this->error('render_outcome_uncertain', 503, $existing->id),
                default => $this->error('render_in_progress', 409, $existing->id),
            };
        }

        try {
            $bytes = $renderer->render($html);
        } catch (RenderFailed $failure) {
            if ($failure->uncertain) {
                DB::table('renders')->where('id', $id)->update(['state' => 'uncertain']);
                logger()->warning('Quotation rendering outcome uncertain', ['request_id' => $id]);

                return $this->error('render_outcome_uncertain', 503, $id);
            }

            DB::transaction(function () use ($pilot, $period, $id) {
                DB::table('pilot_usage')->where('pilot_id', $pilot->id)->where('period', $period)->decrement('consumed');
                DB::table('renders')->where('id', $id)->delete();
            }, 3);
            logger()->warning('Quotation rendering unavailable', ['request_id' => $id]);

            return $this->error('renderer_unavailable', 503, $id);
        }

        DB::table('renders')->where('id', $id)->update([
            'state' => 'completed',
            'pdf_base64' => base64_encode($bytes),
            'expires_at' => now('UTC')->addHour(),
        ]);

        return $this->pdf($bytes, $quote['reference'], $id);
    }

    private function canonical(array $value): array
    {
        if (!array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => is_array($item) ? $this->canonical($item) : $item, $value);
    }

    private function pdf(string $bytes, string $reference, string $requestId)
    {
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$reference.'.pdf"',
            'Cache-Control' => 'no-store',
            'X-Request-ID' => $requestId,
        ]);
    }

    private function error(string $code, int $status, ?string $requestId = null)
    {
        return response()->json(array_filter(['error' => $code, 'request_id' => $requestId]), $status)
            ->header('Cache-Control', 'no-store');
    }
}
