<?php

require_once __DIR__ . '/BaseAiService.php';

class AiAssistant_ThreatDetectionService extends AiAssistant_BaseAiService
{
    public function scan(array $context, array $options = array())
    {
        $heuristics = $this->heuristics($context);
        if ($this->isEmptyMessage($context)) {
            return array(
                'provider' => 'Local heuristics',
                'model' => 'rule-based',
                'mode' => 'local',
                'badge' => 'Local AI',
                'result' => array(
                    'risk_level' => count($heuristics) ? 'Medium' : 'Low',
                    'confidence' => 'Low',
                    'summary' => 'No email body content was available for AI threat analysis. Local header and attachment checks were used only.',
                    'red_flags' => $heuristics,
                    'safe_actions' => array('Open the message and confirm body content is visible before relying on this result.'),
                    'suspicious_links' => array(),
                    'suspicious_attachments' => array(),
                ),
                'heuristics' => $heuristics,
            );
        }

        $result = $this->run('threat_scan', $this->prompts->threat($context), $context, null, array_merge(array('max_tokens' => 800), $options));
        $result['heuristics'] = $heuristics;
        return $result;
    }

    private function isEmptyMessage(array $context)
    {
        return trim((string) (isset($context['body']) ? $context['body'] : '')) === ''
            && trim((string) (isset($context['subject']) ? $context['subject'] : '')) === ''
            && trim((string) (isset($context['from']) ? $context['from'] : '')) === ''
            && empty($context['attachments']);
    }

    private function heuristics(array $context)
    {
        $body = strtolower(isset($context['body']) ? $context['body'] : '');
        $flags = array();
        foreach (array('gift card', 'wire transfer', 'urgent', 'password', 'verify your account', 'invoice attached') as $term) {
            if (strpos($body, $term) !== false) {
                $flags[] = 'Contains suspicious phrase: ' . $term;
            }
        }

        foreach (isset($context['attachments']) ? $context['attachments'] : array() as $name) {
            $lower = strtolower($name);
            if (preg_match('/\\.(exe|js|vbs|scr|bat|cmd|ps1)$/', $lower)) {
                $flags[] = 'Executable attachment: ' . $name;
            }
            if (preg_match('/\\.(docm|xlsm|pptm)$/', $lower)) {
                $flags[] = 'Macro-enabled Office document: ' . $name;
            }
            if (preg_match('/\\.[a-z0-9]+\\.(exe|js|vbs|scr|bat|cmd|ps1)$/', $lower)) {
                $flags[] = 'Double-extension attachment: ' . $name;
            }
        }

        return $flags;
    }
}
