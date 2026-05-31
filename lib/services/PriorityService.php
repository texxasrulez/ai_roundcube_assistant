<?php

require_once __DIR__ . '/BaseAiService.php';

class AiAssistant_PriorityService extends AiAssistant_BaseAiService
{
    public function classify(array $context, array $options = array())
    {
        return $this->run('prioritize', $this->prompts->priority($context), $context, null, array_merge(array('max_tokens' => 300), $options));
    }
}
