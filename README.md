# PDFer

PDFer generates branded Arabic, English, and bilingual quotation PDFs. The browser demo previews fictional quotations, and the pilot API returns a PDF rendered by PDFShift.

## Run locally

Requires PHP 8.2+ with PDO SQLite, mbstring, DOM, and curl, plus Composer.

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
php artisan serve
```

Open http://127.0.0.1:8000 and submit the sample JSON. The preview has a browser print button. It works without a PDFShift account, and uses the browser's print engine to save a PDF. The browser demo accepts fictional sample data only as a usage convention.

## Provision an API pilot

Configure `PDFSHIFT_API_KEY` in the server environment for actual API PDF generation. It remains server-side. Create a pilot and save the API key printed by this command. The database stores only a hash of the key.

```bash
php artisan pdfer:pilot "Demo Agency" --quota=1000
```

Pass `--logo=/path/to/logo.png` to embed a pilot's PNG logo in each document. The approved logo is capped at 256 KB and 2048 × 2048 pixels. Deliver the pilot key securely to the customer.

```bash
curl --fail-with-body -sS \
  -X POST http://127.0.0.1:8000/api/v1/quotations/pdf \
  -H "Authorization: Bearer ${PDFER_PILOT_KEY}" \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: quote-QT-1042-v1' \
  --data-binary @docs/example-quotation.json \
  --output quotation.pdf
```

Send the same idempotency key with retries of the same quotation. Use a fresh key for each new or edited quotation. Reusing a key with changed content returns `409`. The response supplies `X-Request-ID` for a PDF or a request ID in JSON for render errors. Browser preview errors appear above the JSON editor.

| Field | Accepted value |
|---|---|
| `language` | `ar`, `en`, or `bilingual` |
| `currency` | `EGP`, `SAR`, or `USD` |
| `quantity` | Positive decimal string, up to three decimal places |
| `unit_price` | Nonnegative decimal string, up to two decimal places |
| `items` | 1 to 80 entries |
| `reference` | 1 to 40 ASCII letters, digits, underscores, or hyphens |
| `issued_on`, `valid_until` | ISO calendar dates. The optional expiry cannot precede the issue date. |

The API calculates line totals with integer minor units, rounds half cents up per line, and sums the rounded amounts. It accepts JSON request bodies up to 64 KiB and returns PDF output up to 5 MiB.

## Run a pilot

Set `APP_ENV=production`, `APP_DEBUG=false`, and a unique `APP_KEY`. Use HTTPS and serve only `public/` as the web root. Set `SESSION_SECURE_COOKIE=true` with HTTPS, persist the database, and run `php artisan migrate --force`. Schedule `php artisan schedule:run` every minute. Allow at least 30 seconds for the application request, including a renderer timeout of 25 seconds.

The monthly quota resets by UTC calendar month. A completed PDF is available for an hour when retried with the same key. The database holds its base64-encoded bytes for replay, and an hourly scheduled job removes expired bytes. Idempotency metadata remains so replaying an expired request returns `410`. Document data is sent to PDFShift for conversion, so confirm the provider setup is appropriate for pilot customer data. Keep the database and backups private.

A definite renderer rejection releases the reserved credit. A network failure or an unfinished request after two minutes returns `render_outcome_uncertain`. These cases hold one credit because the upstream service might have processed the document. The operator can investigate using the request ID and adjust the pilot's quota if needed. A retry using the original key never submits an uncertain render again. The API logs request IDs for renderer failures without logging quotation content or keys.

Run `composer test` for validation, totals, the demo flow, API authentication, quotas, tenant separation, retries, and provider failure handling. Tests use a fake PDFShift response. Before customer use, test the configured provider with an English, Arabic, bilingual, and multipage quotation and check the resulting documents.

The embedded Arabic font is Noto Sans Arabic. Its license is in `resources/fonts/LICENSE.txt`.
