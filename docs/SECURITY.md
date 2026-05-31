# Security

Security controls:

- API keys stay server-side.
- AJAX actions rely on Roundcube request/session validation.
- Email content is wrapped as untrusted input in prompts.
- AI output is rendered as escaped text in the browser.
- The plugin never auto-sends email, follows links, downloads attachments, or creates filters.
- Threat detection uses probabilistic language and avoids certainty claims.

Threat scanning includes AI analysis plus local attachment and phrase heuristics for common risky patterns.
