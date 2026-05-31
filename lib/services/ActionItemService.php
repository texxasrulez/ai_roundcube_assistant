<?php

require_once __DIR__ . '/BaseAiService.php';

class AiAssistant_ActionItemService extends AiAssistant_BaseAiService
{
    public function extract(array $context, array $options = array())
    {
        return $this->run('extract_actions', $this->prompts->actions($context), $context, null, array_merge(array('max_tokens' => 700), $options));
    }
}
