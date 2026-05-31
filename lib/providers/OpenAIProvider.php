<?php

class AiAssistant_OpenAIProvider extends AiAssistant_OpenAICompatibleProvider
{
    protected $providerName = 'OpenAI';

    public function __construct(array $config)
    {
        if (empty($config['api_key'])) {
            throw new RuntimeException('OpenAI API key is missing. Set $config[\'ai_assistant_providers\'][\'openai\'][\'api_key\'] in server-side plugin config.');
        }

        $config['endpoint'] = isset($config['endpoint']) && $config['endpoint']
            ? $config['endpoint']
            : 'https://api.openai.com/v1';
        parent::__construct($config);
    }
}
