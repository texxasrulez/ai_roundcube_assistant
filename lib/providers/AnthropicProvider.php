<?php

class AiAssistant_AnthropicProvider implements AiAssistant_AiProviderInterface
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generate(array $messages, array $options = array())
    {
        $apiKey = $this->configValue('api_key', '');
        if ($apiKey === '') {
            throw new RuntimeException('Anthropic API key is missing. Set $config[\'ai_assistant_providers\'][\'anthropic\'][\'api_key\'] in server-side plugin config.');
        }

        $system = '';
        $conversation = array();
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system .= $message['content'] . "\n";
                continue;
            }
            $conversation[] = array(
                'role' => $message['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $message['content'],
            );
        }

        $payload = array(
            'model' => $this->model(),
            'max_tokens' => isset($options['max_tokens']) ? (int) $options['max_tokens'] : (int) $this->configValue('max_tokens', 1200),
            'temperature' => isset($options['temperature']) ? (float) $options['temperature'] : (float) $this->configValue('temperature', 0.2),
            'system' => trim($system),
            'messages' => $conversation,
        );

        $data = $this->request($this->configValue('endpoint', 'https://api.anthropic.com/v1/messages'), $payload, array(
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ), $options);

        if (isset($data['content'][0]['text'])) {
            return (string) $data['content'][0]['text'];
        }

        throw new RuntimeException('Anthropic returned an unexpected response.');
    }

    public function name()
    {
        return 'Anthropic';
    }

    public function model()
    {
        return (string) $this->configValue('model', 'claude-3-5-haiku-latest');
    }

    public function isCloud()
    {
        return true;
    }

    private function request($url, array $payload, array $headers, array $options = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout($options));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout($options));
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $status >= 400) {
            throw new RuntimeException($this->formatError($error, 'Anthropic request failed with HTTP ' . $status . '.'));
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Anthropic returned invalid JSON.');
        }
        return $data;
    }

    private function configValue($key, $default)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    private function timeout(array $options = array())
    {
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : (int) $this->configValue('timeout', 30);
        $cap = !empty($options['allow_long_timeout']) ? 300 : 22;
        return max(5, min($timeout, $cap));
    }

    private function connectTimeout(array $options = array())
    {
        $timeout = isset($options['connect_timeout']) ? (int) $options['connect_timeout'] : (int) $this->configValue('connect_timeout', 5);
        return max(2, min($timeout, 5));
    }

    private function formatError($curlError, $fallback)
    {
        if ($curlError && (stripos($curlError, 'timed out') !== false || stripos($curlError, 'timeout') !== false)) {
            return 'Anthropic timed out before returning a response. The provider or selected model may be too slow for this webmail request; try again, use a faster model, or lower the provider timeout/max_tokens.';
        }

        return $curlError ?: $fallback;
    }
}
