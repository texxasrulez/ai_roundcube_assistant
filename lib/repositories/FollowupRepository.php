<?php

class AiAssistant_FollowupRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function candidates($userId, $days)
    {
        if (!$this->db) {
            return array();
        }

        try {
            $threshold = date('Y-m-d H:i:s', time() - ($days * 86400));
            $res = $this->db->query(
                'SELECT message_ref, recipient, subject, sent_at, status FROM ai_assistant_followups WHERE user_id = ? AND status = ? AND sent_at <= ? ORDER BY sent_at ASC',
                $userId,
                'pending',
                $threshold
            );
            $items = array();
            while ($row = $this->db->fetch_assoc($res)) {
                $items[] = $row;
            }
            return $items;
        } catch (Exception $e) {
            return array();
        }
    }
}
