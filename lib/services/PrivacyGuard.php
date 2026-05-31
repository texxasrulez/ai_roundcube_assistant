<?php

class AiAssistant_PrivacyGuard
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function assertAllowed(AiAssistant_AiProviderInterface $provider, array $context, $includesAttachments = false)
    {
        if ($provider->isCloud() && !empty($context['contains_email_body']) && empty($this->config['ai_assistant_allow_cloud_email_body'])) {
            throw new RuntimeException('Cloud AI is disabled for email body content. Set $config[\'ai_assistant_allow_cloud_email_body\'] = true; or use a local provider such as Ollama.');
        }

        if ($provider->isCloud() && $includesAttachments && empty($this->config['ai_assistant_allow_cloud_attachments'])) {
            throw new RuntimeException('Cloud AI is disabled for attachments. Set $config[\'ai_assistant_allow_cloud_attachments\'] = true; or use a local provider such as Ollama.');
        }
    }
}
