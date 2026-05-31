<?php

class AiAssistant_OllamaProvider implements AiAssistant_AiProviderInterface
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generate(array $messages, array $options = array())
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for Ollama calls.');
        }

        $endpoint = rtrim($this->configValue('endpoint', 'http://localhost:11434'), '/') . '/api/chat';
        $payload = array(
            'model' => $this->model(),
            'messages' => $messages,
            'stream' => false,
            'options' => array(
                'temperature' => isset($options['temperature']) ? (float) $options['temperature'] : (float) $this->configValue('temperature', 0.2),
                'num_predict' => isset($options['max_tokens']) ? (int) $options['max_tokens'] : (int) $this->configValue('max_tokens', 1200),
            ),
        );

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout($options));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout($options));
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status >= 400) {
            throw new RuntimeException($this->formatError($error));
        }

        $data = json_decode($raw, true);
        if (isset($data['message']['content'])) {
            return (string) $data['message']['content'];
        }

        throw new RuntimeException('Ollama returned an unexpected response.');
    }

    public function name()
    {
        return 'Ollama';
    }

    public function model()
    {
        return (string) $this->configValue('model', 'llama3.1');
    }

    public function isCloud()
    {
        return false;
    }

    private function configValue($key, $default)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    private function timeout(array $options = array())
    {
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : (int) $this->configValue('timeout', 45);
        $cap = !empty($options['allow_long_timeout']) ? 300 : 22;
        return max(5, min($timeout, $cap));
    }

    private function connectTimeout(array $options = array())
    {
        $timeout = isset($options['connect_timeout']) ? (int) $options['connect_timeout'] : (int) $this->configValue('connect_timeout', 5);
        return max(2, min($timeout, 5));
    }

    private function formatError($curlError)
    {
        if ($curlError && (stripos($curlError, 'timed out') !== false || stripos($curlError, 'timeout') !== false)) {
            return 'Local Ollama timed out before returning a response. The selected model may still be loading or may be too slow for this webmail request; try again, use a smaller model, or lower the provider timeout/max_tokens.';
        }

        return $curlError ?: 'Local Ollama request failed. Confirm Ollama is running and the model is pulled.';
    }
}
