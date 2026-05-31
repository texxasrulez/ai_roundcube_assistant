CREATE TABLE IF NOT EXISTS ai_assistant_analysis_cache (
  id BIGSERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  cache_key VARCHAR(191) NOT NULL,
  payload TEXT NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NOT NULL
);
CREATE INDEX IF NOT EXISTS ai_cache_user_key ON ai_assistant_analysis_cache (user_id, cache_key);
CREATE INDEX IF NOT EXISTS ai_cache_expires ON ai_assistant_analysis_cache (expires_at);

CREATE TABLE IF NOT EXISTS ai_assistant_followups (
  id BIGSERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  message_ref VARCHAR(255) NOT NULL,
  recipient VARCHAR(255) NOT NULL,
  subject VARCHAR(998),
  sent_at TIMESTAMP NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP
);
CREATE INDEX IF NOT EXISTS ai_followup_user_status ON ai_assistant_followups (user_id, status, sent_at);

CREATE TABLE IF NOT EXISTS ai_assistant_user_prefs (
  id BIGSERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  pref_key VARCHAR(191) NOT NULL,
  pref_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  UNIQUE (user_id, pref_key)
);

CREATE TABLE IF NOT EXISTS ai_assistant_audit_log (
  id BIGSERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  action_type VARCHAR(64) NOT NULL,
  provider VARCHAR(64) NOT NULL,
  model VARCHAR(128) NOT NULL,
  ai_mode VARCHAR(16) NOT NULL,
  message_ref VARCHAR(255),
  created_at TIMESTAMP NOT NULL
);
CREATE INDEX IF NOT EXISTS ai_audit_user_created ON ai_assistant_audit_log (user_id, created_at);
CREATE INDEX IF NOT EXISTS ai_audit_action ON ai_assistant_audit_log (action_type);
