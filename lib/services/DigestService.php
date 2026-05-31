<?php

require_once __DIR__ . '/BaseAiService.php';

class AiAssistant_DigestService extends AiAssistant_BaseAiService
{
    public function digest(array $context)
    {
        return $this->run('digest', $this->prompts->digest($context), $context);
    }
}
