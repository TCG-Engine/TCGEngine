-- Hosted Card Code service metadata.
-- The existing card_abilities table remains the current authored state. These tables add
-- scoped API credentials and recoverable daily snapshots without changing generated code.

CREATE TABLE IF NOT EXISTS `card_code_api_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `token_name` varchar(128) NOT NULL,
  `token_hash` binary(32) NOT NULL,
  `root_name` varchar(64) NULL COMMENT 'NULL grants the token access to every root',
  `scopes` varchar(255) NOT NULL DEFAULT 'read',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `uq_card_code_token_hash` (`token_hash`),
  KEY `idx_card_code_token_root` (`root_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `card_code_checkpoints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `root_name` varchar(64) NOT NULL,
  `checkpoint_date` date NOT NULL,
  `payload` mediumblob NOT NULL COMMENT 'gzip-compressed canonical JSON ability rows',
  `payload_sha256` char(64) NOT NULL,
  `ability_count` int unsigned NOT NULL DEFAULT 0,
  `created_by` varchar(128) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY `uq_card_code_checkpoint_day` (`root_name`, `checkpoint_date`),
  KEY `idx_card_code_checkpoint_root_created` (`root_name`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
