<?php

namespace Tests\Feature;

use App\Services\Quotation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class QuotationPilotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    private const PDF = "%PDF-1.7\nTest double\n%%EOF";

    public function test_demo_previews_bilingual_quote_without_using_a_provider(): void
    {
        Http::preventStrayRequests();
        $this->get('/')->assertOk()->assertSee('Preview quotation');
        $this->post('/preview', ['payload' => json_encode(Quotation::sample(), JSON_THROW_ON_ERROR)])
            ->assertOk()
            ->assertSee('عرض سعر')
            ->assertSee('Print or save as PDF')
            ->assertSee('EGP 18,000.00');
        Http::assertNothingSent();
    }

    public function test_api_requires_key_and_rejects_invalid_values_before_rendering(): void
    {
        Http::fake();
        $this->postJson('/api/v1/quotations/pdf', Quotation::sample())
            ->assertUnauthorized()->assertJsonPath('error', 'invalid_api_key');

        [$key] = $this->pilot();
        $quote = Quotation::sample();
        $quote['items'][0]['unit_price'] = 19.99;
        $this->sendQuote($key, 'validate', $quote)
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_price');
        Http::assertNothingSent();
    }

    public function test_completed_pdf_is_replayed_within_the_same_pilot_without_using_another_credit(): void
    {
        [$key, $pilotId] = $this->pilot();
        [$otherKey, $otherPilotId] = $this->pilot();
        $this->fakePdf();

        $first = $this->sendQuote($key, 'first')->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="QT-1042.pdf"');
        $this->assertSame(self::PDF, $first->getContent());

        $this->sendQuote($key, 'first')->assertOk()->assertHeader('X-Request-ID', $first->headers->get('X-Request-ID'));
        $this->sendQuote($key, 'second')->assertTooManyRequests()->assertJsonPath('error', 'quota_exhausted');
        $changed = array_replace(Quotation::sample(), ['reference' => 'QT-1043']);
        $this->sendQuote($key, 'first', $changed)->assertConflict()->assertJsonPath('error', 'idempotency_key_conflict');

        $this->sendQuote($otherKey, 'first')->assertOk();
        $this->assertDatabaseHas('pilot_usage', ['pilot_id' => $pilotId, 'consumed' => 1]);
        $this->assertDatabaseHas('pilot_usage', ['pilot_id' => $otherPilotId, 'consumed' => 1]);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'provider-test-key')
            && str_contains($request['source'], 'عرض سعر')
            && !str_contains($request['source'], 'Print or save as PDF'));
    }

    public function test_expired_success_is_not_returned_or_rerendered(): void
    {
        [$key] = $this->pilot();
        $this->fakePdf();
        $this->sendQuote($key, 'expire')->assertOk();
        $this->travel(61)->minutes();
        $this->sendQuote($key, 'expire')->assertGone()->assertJsonPath('error', 'render_expired');
        Http::assertSentCount(1);
    }

    public function test_definite_rejection_releases_credit_but_connection_failure_does_not_retry(): void
    {
        [$key, $pilotId] = $this->pilot();
        config(['services.pdfshift.key' => 'provider-test-key']);
        Http::fake(['api.pdfshift.io/*' => Http::response(['message' => 'provider private detail'], 401)]);

        $this->sendQuote($key, 'retry')->assertServiceUnavailable()->assertJsonPath('error', 'renderer_unavailable')
            ->assertDontSee('provider private detail');
        $this->assertDatabaseHas('pilot_usage', ['pilot_id' => $pilotId, 'consumed' => 0]);

        Http::fake(['api.pdfshift.io/*' => Http::failedConnection()]);
        $this->sendQuote($key, 'retry')->assertServiceUnavailable()->assertJsonPath('error', 'render_outcome_uncertain');
        $this->fakePdf();
        $this->sendQuote($key, 'retry')->assertServiceUnavailable()->assertJsonPath('error', 'render_outcome_uncertain');
        Http::assertNothingSent();
        $this->assertDatabaseHas('pilot_usage', ['pilot_id' => $pilotId, 'consumed' => 1]);
    }

    private function pilot(): array
    {
        $key = 'pdfer_'.bin2hex(random_bytes(32));
        return [$key, DB::table('pilots')->insertGetId([
            'name' => 'Test agency',
            'key_hash' => hash('sha256', $key),
            'monthly_quota' => 1,
            'active' => true,
        ])];
    }

    private function fakePdf(): void
    {
        config(['services.pdfshift.key' => 'provider-test-key']);
        Http::fake(['api.pdfshift.io/*' => Http::response(self::PDF, 200)]);
    }

    private function sendQuote(string $key, string $idempotencyKey, ?array $quote = null)
    {
        return $this->postJson('/api/v1/quotations/pdf', $quote ?? Quotation::sample(), [
            'Authorization' => 'Bearer '.$key,
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }
}
