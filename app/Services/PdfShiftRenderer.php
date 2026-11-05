<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class PdfShiftRenderer implements PdfRenderer
{
    public function render(string $html): string
    {
        $key = config('services.pdfshift.key');
        if (!is_string($key) || $key === '') {
            throw new RenderFailed();
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $key])
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(5)
                ->timeout(25)
                ->post('https://api.pdfshift.io/v3/convert/pdf', [
                    'source' => $html,
                    'format' => 'A4',
                ]);
        } catch (ConnectionException) {
            throw new RenderFailed(uncertain: true);
        }

        if (!$response->successful()) {
            throw new RenderFailed(uncertain: $response->serverError());
        }

        $bytes = $response->body();
        if (!str_starts_with($bytes, '%PDF-') || strlen($bytes) > 5 * 1024 * 1024) {
            throw new RenderFailed(uncertain: true);
        }

        return $bytes;
    }
}
