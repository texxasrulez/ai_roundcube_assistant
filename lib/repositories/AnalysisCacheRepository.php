<?php

class AiAssistant_AnalysisCacheRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function get($userId, $cacheKey)
    {
        if (!$this->db) {
            return null;
        }

        try {
            $res = $this->db->query('SELECT payload FROM ai_assistant_analysis_cache WHERE user_id = ? AND cache_key = ? AND expires_at > ?', $userId, $cacheKey, date('Y-m-d H:i:s'));
            $row = $this->db->fetch_assoc($res);
            return $row ? json_decode($row['payload'], true) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function put($userId, $cacheKey, array $payload, $minutes)
    {
        if (!$this->db) {
            return;
        }

        try {
            $this->db->query(
                'INSERT INTO ai_assistant_analysis_cache (user_id, cache_key, payload, expires_at, created_at) VALUES (?, ?, ?, ?, ?)',
                $userId,
                $cacheKey,
                json_encode($payload),
                date('Y-m-d H:i:s', time() + ($minutes * 60)),
                date('Y-m-d H:i:s')
            );
        } catch (Exception $e) {
        }
    }
}
