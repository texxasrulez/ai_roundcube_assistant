<?php

require_once __DIR__ . '/BaseAiService.php';

class AiAssistant_ComposeService extends AiAssistant_BaseAiService
{
    public function compose(array $payload, array $options = array())
    {
        $payload['prompt'] = $this->limitText(isset($payload['prompt']) ? $payload['prompt'] : '', 2000);
        $payload['context'] = $this->limitText(isset($payload['context']) ? $payload['context'] : '', 800);
        $context = array('contains_email_body' => !empty($payload['context']), 'message_ref' => null);
        return $this->run('compose', $this->prompts->compose($payload), $context, null, array_merge(array('max_tokens' => 220, 'timeout' => 22), $options));
    }

    public function rewrite(array $payload, array $options = array())
    {
        $payload['text'] = $this->limitText(isset($payload['text']) ? $payload['text'] : '', 3000);
        $payload['context'] = $this->limitText(isset($payload['context']) ? $payload['context'] : '', 800);
        $context = array('contains_email_body' => !empty($payload['context']), 'message_ref' => null);
        return $this->run('rewrite', $this->prompts->rewrite($payload), $context, null, array_merge(array('max_tokens' => 220, 'timeout' => 22), $options));
    }

    private function limitText($text, $limit)
    {
        $text = trim((string) $text);
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $limit);
        }
        return substr($text, 0, $limit);
    }
}
