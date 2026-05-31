<?php

abstract class AiAssistant_BaseAiService
{
    protected $factory;
    protected $prompts;
    protected $privacy;
    protected $audit;

    public function __construct(AiAssistant_AiProviderFactory $factory, AiAssistant_PromptBuilder $prompts, AiAssistant_PrivacyGuard $privacy, AiAssistant_AuditLogService $audit)
    {
        $this->factory = $factory;
        $this->prompts = $prompts;
        $this->privacy = $privacy;
        $this->audit = $audit;
    }

    protected function run($action, array $messages, array $context, $preferredProvider = null, array $options = array())
    {
        $provider = $this->factory->create($preferredProvider);
        $this->privacy->assertAllowed($provider, $context, !empty($context['has_attachments']));
        $raw = $provider->generate($messages, $options);
        $this->audit->record($action, $provider, isset($context['message_ref']) ? $context['message_ref'] : null);
        return array(
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'mode' => $provider->isCloud() ? 'cloud' : 'local',
            'badge' => $provider->isCloud() ? $provider->name() : 'Local AI',
            'result' => $this->parseJson($raw),
            'raw' => $raw,
        );
    }

    protected function parseJson($raw)
    {
        $text = trim((string) $raw);
        $text = preg_replace('/^```(?:json)?\\s*|```$/m', '', $text);
        $text = trim($text);
        $data = json_decode($text, true);
        if (!is_array($data) && preg_match('/\\{.*\\}/s', $text, $matches)) {
            $data = json_decode($matches[0], true);
        }
        if (!is_array($data) && substr($text, -2) === '}}') {
            $data = json_decode(substr($text, 0, -1), true);
        }
        return is_array($data) ? $data : array('text' => (string) $raw);
    }
}
