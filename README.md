# AI Roundcube Assistant

AI Roundcube Assistant is a Roundcube plugin that adds AI-assisted compose tools, message summarization, threat detection, action extraction, follow-up tracking, priority classification, newsletter digests, and an optional inbox health dashboard.

## Requirements

- Roundcube 1.4 or newer, with tested conventions targeting 1.6
- PHP 7.4 or newer
- PHP cURL extension for AI provider calls
- PHP-FPM `fastcgi_finish_request()` support for long-running async AI jobs
- Optional database tables for cache, follow-ups, preferences, and audit logging
- Optional Ollama for local, offline AI

## Installation

1. Place this directory at `plugins/ai_roundcube_assistant/`.
2. Copy `config.inc.php.dist` to `config.inc.php` and edit provider settings.
3. Enable the plugin in Roundcube `config/config.inc.php`:

```php
$config['plugins'][] = 'ai_roundcube_assistant';
```

4. Optional: load the matching SQL file from `SQL/` into the Roundcube database.

Basic compose and summarize requests do not require the optional database tables. Audit, cache, preferences, and follow-up storage degrade gracefully if tables are absent.

## Providers

Supported providers:

- Ollama local LLM
- OpenAI official API
- OpenAI-compatible API endpoint
- Anthropic API
- Google Gemini API

The default config uses Ollama and blocks cloud email body processing. API keys stay in PHP config and are never sent to browser JavaScript.

To use OpenAI for message analysis, enable the provider and explicitly allow cloud email body processing:

```php
$config['ai_assistant_default_provider'] = 'openai';
$config['ai_assistant_providers']['openai']['enabled'] = true;
$config['ai_assistant_allow_cloud_email_body'] = true;
```

## Local Ollama Setup

Install and run Ollama on the host that serves Roundcube, then pull a model:

```sh
ollama pull llama3.1
```

Set:

```php
$config['ai_assistant_default_provider'] = 'ollama';
$config['ai_assistant_providers']['ollama']['endpoint'] = 'http://localhost:11434';
$config['ai_assistant_providers']['ollama']['model'] = 'llama3.1';
```

## Privacy And Security

- Email bodies are not sent to cloud providers unless `$config['ai_assistant_allow_cloud_email_body'] = true`.
- Attachments are not sent to cloud providers unless `$config['ai_assistant_allow_cloud_attachments'] = true`.
- Prompt templates label email content as untrusted and instruct models to ignore instructions embedded in messages.
- The plugin never auto-sends email, opens links, downloads attachments, or creates filters.
- Generated compose content is inserted only after user approval.

## Skin Support

The plugin ships styles for `elastic`, `larry`, `classic`, and colored Larry variants: `autumn_larry`, `black_larry`, `blue_larry`, `green_larry`, `grey_larry`, `pink_larry`, `plata_larry`, `summer_larry`, `teal_larry`, and `violet_larry`.

Colored Larry styling is lightweight and independent. The referenced colored Larry repositories were used only as visual/color references; this plugin does not require them.

## Troubleshooting

- Local AI unavailable: confirm Ollama is running and the configured model is pulled.
- Cloud disabled warning: enable `$config['ai_assistant_allow_cloud_email_body']` only if your policy allows email content to leave the server.
- Missing API key: set the provider `api_key` in server-side plugin config.
- Provider timeout: synchronous provider HTTP timeouts are capped at 22 seconds so Roundcube can return a plugin error before Apache/PHP-FPM returns a 504.
- Slow local models: compose, summarize, threat scan, action extraction, and priority buttons use async jobs when PHP-FPM supports `fastcgi_finish_request()`. Set `$config['ai_assistant_async_timeout']` up to `300` seconds for larger Ollama models.
- Blank AI panel: check browser console and Roundcube logs with `$config['ai_assistant_log_level']`.
