<?php

class AiAssistant_UserPrefsRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function get($userId, $key, $default = null)
    {
        if (!$this->db) {
            return $default;
        }

        try {
            $res = $this->db->query('SELECT pref_value FROM ai_assistant_user_prefs WHERE user_id = ? AND pref_key = ?', $userId, $key);
            $row = $this->db->fetch_assoc($res);
            return $row ? json_decode($row['pref_value'], true) : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    public function set($userId, $key, $value)
    {
        if (!$this->db) {
            return;
        }

        try {
            $this->db->query(
                'INSERT INTO ai_assistant_user_prefs (user_id, pref_key, pref_value, updated_at) VALUES (?, ?, ?, ?)',
                $userId,
                $key,
                json_encode($value),
                date('Y-m-d H:i:s')
            );
        } catch (Exception $e) {
        }
    }
}
