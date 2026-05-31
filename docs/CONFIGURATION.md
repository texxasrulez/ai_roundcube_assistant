# Configuration

All settings live in `config.inc.php`.

Important keys:

- `$config['ai_assistant_enabled']`
- `$config['ai_assistant_default_provider']`
- `$config['ai_assistant_providers']`
- `$config['ai_assistant_allow_cloud_email_body']`
- `$config['ai_assistant_allow_cloud_attachments']`
- `$config['ai_assistant_allow_external_reputation_checks']`
- `$config['ai_assistant_enable_threat_detection']`
- `$config['ai_assistant_enable_summarization']`
- `$config['ai_assistant_enable_compose_tools']`
- `$config['ai_assistant_enable_followup_tracker']`
- `$config['ai_assistant_enable_dashboard']`
- `$config['ai_assistant_followup_days']`
- `$config['ai_assistant_async_timeout']`
- `$config['ai_assistant_log_level']`
- `$config['ai_assistant_cache_minutes']`
- `$config['ai_assistant_max_email_chars']`

Disable individual features by setting the relevant `ai_assistant_enable_*` key to `false`.

Synchronous provider `timeout` values above 22 seconds are capped by the plugin to avoid webserver/PHP-FPM 504 responses.

Compose, summarize, threat scan, action extraction, and priority toolbar actions use async jobs when PHP-FPM supports `fastcgi_finish_request()`. Async jobs are stored under Roundcube's configured `temp_dir`, release the PHP session before the slow provider call, and are polled by the browser until complete. `$config['ai_assistant_async_timeout']` controls the long-running provider timeout for these jobs and is capped at 300 seconds.
