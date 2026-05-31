<?php

class AiAssistant_GeminiProvider implements AiAssistant_AiProviderInterface
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
            throw new RuntimeException('Gemini API key is missing. Set $config[\'ai_assistant_providers\'][\'gemini\'][\'api_key\'] in server-side plugin config.');
        }

        $parts = array();
        foreach ($messages as $message) {
            $parts[] = array('text' => strtoupper($message['role']) . ":\n" . $message['content']);
        }

        $payload = array(
            'contents' => array(array('role' => 'user', 'parts' => $parts)),
            'generationConfig' => array(
                'temperature' => isset($options['temperature']) ? (float) $options['temperature'] : (float) $this->configValue('temperature', 0.2),
                'maxOutputTokens' => isset($options['max_tokens']) ? (int) $options['max_tokens'] : (int) $this->configValue('max_tokens', 1200),
            ),
        );

        $endpoint = rtrim($this->configValue('endpoint', 'https://generativelanguage.googleapis.com/v1beta'), '/')
            . '/models/' . rawurlencode($this->model()) . ':generateContent?key=' . rawurlencode($apiKey);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout($options));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout($options));
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $status >= 400) {
            throw new RuntimeException($this->formatError($error, 'Gemini request failed with HTTP ' . $status . '.'));
        }

        $data = json_decode($raw, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return (string) $data['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new RuntimeException('Gemini returned an unexpected response.');
    }

    public function name()
    {
        return 'Google Gemini';
    }

    public function model()
    {
        return (string) $this->configValue('model', 'gemini-1.5-flash');
    }

    public function isCloud()
    {
        return true;
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
            return 'Google Gemini timed out before returning a response. The provider or selected model may be too slow for this webmail request; try again, use a faster model, or lower the provider timeout/max_tokens.';
        }

        return $curlError ?: $fallback;
    }
}
