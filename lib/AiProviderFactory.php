<?php

class AiAssistant_AiProviderFactory
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function create($preferred = null)
    {
        $name = $preferred ?: $this->config['ai_assistant_default_provider'];
        $providers = isset($this->config['ai_assistant_providers']) && is_array($this->config['ai_assistant_providers'])
            ? $this->config['ai_assistant_providers']
            : array();

        if (!isset($providers[$name]) || !is_array($providers[$name])) {
            throw new RuntimeException("AI provider '{$name}' is not configured. Check ai_assistant_default_provider and ai_assistant_providers.");
        }

        if (empty($providers[$name]['enabled'])) {
            throw new RuntimeException("AI provider '{$name}' is disabled. Set \$config['ai_assistant_providers']['{$name}']['enabled'] = true; or choose an enabled default provider.");
        }

        $provider = $providers[$name];
        switch ($name) {
            case 'ollama':
                return new AiAssistant_OllamaProvider($provider);
            case 'openai':
                return new AiAssistant_OpenAIProvider($provider);
            case 'openai_compatible':
                return new AiAssistant_OpenAICompatibleProvider($provider);
            case 'anthropic':
                return new AiAssistant_AnthropicProvider($provider);
            case 'gemini':
                return new AiAssistant_GeminiProvider($provider);
            default:
                throw new RuntimeException('Unknown AI provider.');
        }
    }
}
