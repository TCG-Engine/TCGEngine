-- Card Abilities Table
-- Stores custom ability code for individual cards, linked to game macros
-- Used by CardEditor to manage card abilities and by zzGameCodeGenerator to generate macro implementations

-- IF NOT EXISTS matters: this file runs as a docker initdb hook on a fresh volume AND is executed
-- by CardAbilityDB::EnsureSchema() on every connection, so it must be safe to re-run on a database
-- that already has the table and rows.
CREATE TABLE IF NOT EXISTS `card_abilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `root_name` varchar(64) NOT NULL COMMENT 'Game root name (e.g., RBSim, SoulMasters)',
  `card_id` varchar(128) NOT NULL COMMENT 'Card identifier (including canonical asset IDs)',
  `macro_name` varchar(128) NOT NULL COMMENT 'Name of the macro this ability implements',
  `ability_type` varchar(32) NOT NULL DEFAULT 'macro' COMMENT 'macro for direct card macro abilities, listener for zone-active macro listeners',
  `ability_code` longtext NOT NULL COMMENT 'PHP/code body for the ability implementation',
  `prereq_code` longtext NULL COMMENT 'Optional PHP/code body returning whether this macro ability can run',
  `listener_zones` text NULL COMMENT 'Comma-separated schema zone names where listener abilities are active',
  `ability_name` varchar(128) NULL COMMENT 'Optional human-readable name for the ability',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_implemented` boolean NOT NULL DEFAULT 0,
  KEY `idx_root_card` (`root_name`, `card_id`),
  KEY `idx_root_macro` (`root_name`, `macro_name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'Custom card ability implementations linked to game macros';
