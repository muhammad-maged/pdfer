# PDfer

Alpha owns product scope, acceptance, and release decisions. Bravo owns implementation and validation. Keep changes reviewable in GitHub. Alpha can merge verified work under the founder's standing authorization.

## First paid pilot

Generate branded Arabic, English, and bilingual quotations from structured data. Use one fixed layout with a browser demo and authenticated API. Provision pilot access manually. Pricing to test is $99 setup for one branded template and $29/month thereafter.

- Accept bounded structured fields for quotation reference, date, business, customer, currency, line items, and optional notes. Escape input and render our own markup.
- Start with EGP, SAR, and USD. Calculate amounts on the server in integer minor units and use explicit rounding rules.
- Keep PDFShift credentials server-side. Use fictional sample data. Never commit secrets, customer documents, or credentials. HTML preview is distinct from PDF export.
- Before a paid pilot, verify real Arabic and mixed-language PDFs, page breaks, tenant isolation, usage limits under concurrent requests, and safe retry behavior. Avoid recording document contents in logs.
- Keep the first pilot focused on quotations. Defer visual editing, checkout automation, and additional document types until demand is validated.
