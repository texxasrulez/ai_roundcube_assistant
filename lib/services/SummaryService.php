<?php

require_once __DIR__ . '/BaseAiService.php';

class AiAssistant_SummaryService extends AiAssistant_BaseAiService
{
    public function summarize(array $context, $mode, array $options = array())
    {
        return $this->run('summarize', $this->prompts->summary($context, $mode), $context, null, array_merge(array('max_tokens' => 700), $options));
    }
}
