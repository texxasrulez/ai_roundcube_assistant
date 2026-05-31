<?php

class AiAssistant_AuditLogService
{
    private $db;
    private $config;
    private $userId;

    public function __construct($db, array $config, $userId)
    {
        $this->db = $db;
        $this->config = $config;
        $this->userId = $userId;
    }

    public function record($action, AiAssistant_AiProviderInterface $provider, $messageRef = null)
    {
        if (empty($this->db) || $this->config['ai_assistant_log_level'] === 'off') {
            return;
        }

        try {
            $this->db->query(
                'INSERT INTO ai_assistant_audit_log (user_id, action_type, provider, model, ai_mode, message_ref, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                $this->userId,
                $action,
                $provider->name(),
                $provider->model(),
                $provider->isCloud() ? 'cloud' : 'local',
                $messageRef,
                date('Y-m-d H:i:s')
            );
        } catch (Exception $e) {
            // Optional table may not exist; audit logging must not break mail flow.
        }
    }
}
