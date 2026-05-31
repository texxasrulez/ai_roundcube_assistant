CREATE TABLE IF NOT EXISTS ai_assistant_analysis_cache (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  cache_key TEXT NOT NULL,
  payload TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS ai_cache_user_key ON ai_assistant_analysis_cache (user_id, cache_key);
CREATE INDEX IF NOT EXISTS ai_cache_expires ON ai_assistant_analysis_cache (expires_at);

CREATE TABLE IF NOT EXISTS ai_assistant_followups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  message_ref TEXT NOT NULL,
  recipient TEXT NOT NULL,
  subject TEXT,
  sent_at TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  created_at TEXT NOT NULL,
  updated_at TEXT
);
CREATE INDEX IF NOT EXISTS ai_followup_user_status ON ai_assistant_followups (user_id, status, sent_at);

CREATE TABLE IF NOT EXISTS ai_assistant_user_prefs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  pref_key TEXT NOT NULL,
  pref_value TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (user_id, pref_key)
);

CREATE TABLE IF NOT EXISTS ai_assistant_audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  action_type TEXT NOT NULL,
  provider TEXT NOT NULL,
  model TEXT NOT NULL,
  ai_mode TEXT NOT NULL,
  message_ref TEXT,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS ai_audit_user_created ON ai_assistant_audit_log (user_id, created_at);
CREATE INDEX IF NOT EXISTS ai_audit_action ON ai_assistant_audit_log (action_type);
