# Providers

Provider config is stored in `$config['ai_assistant_providers']`.

Use `ollama` for local offline mode:

```php
$config['ai_assistant_default_provider'] = 'ollama';
$config['ai_assistant_providers']['ollama']['enabled'] = true;
```

Use `openai`, `openai_compatible`, `anthropic`, or `gemini` for cloud mode. Cloud requests carrying email body content are blocked unless explicitly allowed:

```php
$config['ai_assistant_default_provider'] = 'openai';
$config['ai_assistant_providers']['openai']['enabled'] = true;
$config['ai_assistant_allow_cloud_email_body'] = true;
```

OpenAI-compatible endpoints must expose `/chat/completions` under the configured base URL.
