CREATE TABLE workspace (
  root TEXT PRIMARY KEY,
  version INTEGER NOT NULL DEFAULT 0
);
INSERT INTO workspace(root) VALUES ('GrandArchiveSim');

-- A failing guard rolls back the entire D1 batch, including the checkpoint and mutation.
CREATE TABLE write_guard (ok INTEGER NOT NULL CHECK (ok = 1));
CREATE TABLE card_abilities (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  root_name TEXT NOT NULL REFERENCES workspace(root),
  card_id TEXT NOT NULL,
  macro_name TEXT NOT NULL DEFAULT '',
  ability_type TEXT NOT NULL DEFAULT 'macro',
  ability_code TEXT NOT NULL DEFAULT '',
  prereq_code TEXT,
  listener_zones TEXT,
  ability_name TEXT,
  is_implemented INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX card_abilities_card ON card_abilities(root_name, card_id);
CREATE TABLE tokens (
  token_hash TEXT PRIMARY KEY,
  token_name TEXT NOT NULL,
  root TEXT NOT NULL REFERENCES workspace(root),
  role TEXT NOT NULL CHECK(role IN ('reader', 'developer', 'maintainer')),
  expires_at TEXT NOT NULL,
  revoked INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE checkpoints (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  root_name TEXT NOT NULL REFERENCES workspace(root),
  checkpoint_date TEXT NOT NULL,
  payload_sha256 TEXT NOT NULL,
  ability_count INTEGER NOT NULL,
  created_by TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(root_name, checkpoint_date)
);
-- One row per ability avoids D1's per-value size limit for large workspace backups.
CREATE TABLE checkpoint_abilities (
  checkpoint_id INTEGER NOT NULL REFERENCES checkpoints(id),
  ability_id INTEGER NOT NULL,
  payload TEXT NOT NULL,
  PRIMARY KEY(checkpoint_id, ability_id)
);
