# Manual Test Cases

1. Provider config loading: enable only `ollama`, open a message, click `Summarize`, and confirm the panel shows `Local AI`.
2. Privacy guard: set default provider to `openai` with cloud email body disabled, click `Summarize`, and confirm a cloud-disabled warning appears.
3. Prompt-injection wrapper: summarize an email containing "ignore previous instructions"; confirm output still summarizes instead of obeying that instruction.
4. Threat JSON parsing: use a test provider response with `risk_level`, `confidence`, `summary`, `red_flags`, `safe_actions`, `suspicious_links`, and `suspicious_attachments`.
5. Skin CSS loading: switch Roundcube skins among Elastic, Larry, Classic, and colored Larry variants; confirm the panel remains readable.
6. AJAX permission checks: submit an expired Roundcube form token and confirm the request returns an invalid request warning.
