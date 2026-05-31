<?php

class AiAssistant_OpenAICompatibleProvider implements AiAssistant_AiProviderInterface
{
    protected $config;
    protected $providerName = 'OpenAI-compatible';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generate(array $messages, array $options = array())
    {
        $endpoint = rtrim($this->configValue('endpoint', ''), '/') . '/chat/completions';
        $apiKey = $this->configValue('api_key', '');
        if (!$endpoint || $endpoint === '/chat/completions') {
            throw new RuntimeException('AI endpoint is missing.');
        }

        $payload = array(
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => isset($options['temperature']) ? (float) $options['temperature'] : (float) $this->configValue('temperature', 0.2),
            'max_tokens' => isset($options['max_tokens']) ? (int) $options['max_tokens'] : (int) $this->configValue('max_tokens', 1200),
        );

        $headers = array('Content-Type: application/json');
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $data = $this->request($endpoint, $payload, $headers, $options);
        if (isset($data['choices'][0]['message']['content'])) {
            return (string) $data['choices'][0]['message']['content'];
        }

        throw new RuntimeException('AI provider returned an unexpected response.');
    }

    public function name()
    {
        return $this->providerName;
    }

    public function model()
    {
        return (string) $this->configValue('model', 'gpt-4o-mini');
    }

    public function isCloud()
    {
        return true;
    }

    protected function request($url, array $payload, array $headers, array $options = array())
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for AI provider calls.');
        }

        $responseHeaders = array();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout($options));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout($options));
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $length = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return $length;
        });
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status >= 400) {
            throw new RuntimeException($this->formatHttpError($status, $raw, $error, $responseHeaders));
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('AI provider returned invalid JSON.');
        }

        return $data;
    }

    protected function formatHttpError($status, $raw, $curlError, array $headers)
    {
        if ($curlError) {
            if (stripos($curlError, 'timed out') !== false || stripos($curlError, 'timeout') !== false) {
                return 'AI provider timed out before returning a response. The provider or selected model may be too slow for this webmail request; try again, use a faster model, or lower the provider timeout/max_tokens.';
            }
            return $curlError;
        }

        $message = 'AI provider request failed with HTTP ' . $status . '.';
        $data = json_decode((string) $raw, true);
        if (isset($data['error']['message']) && $data['error']['message'] !== '') {
            $message .= ' ' . $data['error']['message'];
        } elseif (isset($data['message']) && $data['message'] !== '') {
            $message .= ' ' . $data['message'];
        }

        if ($status === 429) {
            $message .= ' This usually means the provider rate limit, token limit, quota, or billing usage limit was reached.';
            if (!empty($headers['retry-after'])) {
                $message .= ' Retry after ' . $headers['retry-after'] . ' seconds.';
            } elseif (!empty($headers['x-ratelimit-reset-requests'])) {
                $message .= ' Request limit resets in ' . $headers['x-ratelimit-reset-requests'] . '.';
            } elseif (!empty($headers['x-ratelimit-reset-tokens'])) {
                $message .= ' Token limit resets in ' . $headers['x-ratelimit-reset-tokens'] . '.';
            }
        }

        return $message;
    }

    protected function timeout(array $options = array())
    {
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : (int) $this->configValue('timeout', 30);
        $cap = !empty($options['allow_long_timeout']) ? 300 : 22;
        return max(5, min($timeout, $cap));
    }

    protected function connectTimeout(array $options = array())
    {
        $timeout = isset($options['connect_timeout']) ? (int) $options['connect_timeout'] : (int) $this->configValue('connect_timeout', 5);
        return max(2, min($timeout, 5));
    }

    protected function configValue($key, $default)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
}
