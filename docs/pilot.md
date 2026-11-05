# PDfer pilot

The first buyer is a software agency that already generates quotations for clients using an existing application. PDfer receives structured quotation data and returns a branded PDF in Arabic, English, or both.

## First delivery

- Show a fictional quotation with mixed Arabic and Latin text, line items, and computed totals. Include a multi-page case for review.
- Ask the agency for a redacted quotation and a sample payload. Agree on the layout and supported fields before onboarding.
- Offer one branded template, one revision, and integration assistance for a $99 setup fee. Test a $29 monthly plan after the pilot. Provision access and usage limits manually.
- Treat the first paid delivery and the customer's first independently generated quotation as separate milestones.

## Release checks

- Generate a real provider PDF in each supported language mode. Check Arabic shaping, embedded font coverage, mixed-direction names and references, totals, repeated table headers, and page breaks.
- Verify deterministic amounts, input bounds, escaped text, and safe errors for malformed requests and provider failures.
- Verify a pilot key cannot access another account's data. Check duplicate requests, concurrent usage limits, and cleanup of stored output before accepting customer documents.
- Use fictional data in the public demo. Keep the renderer credential in the deployment environment. Confirm how production input and output are handled before onboarding customers.

Keep customer feedback focused on document fidelity and integration effort. Reuse a layout when multiple agencies ask for the same change.
