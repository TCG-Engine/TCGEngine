CREATE TABLE IF NOT EXISTS ce_games (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  game_uuid VARCHAR(36) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(120) UNIQUE NOT NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS ce_sets (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  set_uuid VARCHAR(36) UNIQUE NOT NULL,
  game_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ce_sets_game_slug (game_id, slug),
  KEY idx_ce_sets_game (game_id)
);

CREATE TABLE IF NOT EXISTS ce_templates (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  template_uuid VARCHAR(36) UNIQUE NOT NULL,
  game_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  description TEXT NULL,
  canvas_width INT NOT NULL DEFAULT 750,
  canvas_height INT NOT NULL DEFAULT 1050,
  canvas_background_color VARCHAR(32) NULL DEFAULT '#ffffff',
  canvas_background_asset_id BIGINT NULL,
  safe_area_padding INT NULL DEFAULT 40,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ce_templates_game_slug (game_id, slug),
  KEY idx_ce_templates_game (game_id)
);

CREATE TABLE IF NOT EXISTS ce_template_fields (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  field_uuid VARCHAR(36) UNIQUE NOT NULL,
  template_id BIGINT NOT NULL,
  field_key VARCHAR(120) NOT NULL,
  label VARCHAR(255) NOT NULL,
  field_type VARCHAR(32) NOT NULL,
  help_text TEXT NULL,
  default_value TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  settings_json LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ce_template_fields_key (template_id, field_key),
  KEY idx_ce_template_fields_sort (template_id, sort_order)
);

CREATE TABLE IF NOT EXISTS ce_template_layout_elements (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  element_uuid VARCHAR(36) UNIQUE NOT NULL,
  template_id BIGINT NOT NULL,
  element_type VARCHAR(32) NOT NULL,
  field_id BIGINT NULL,
  asset_id BIGINT NULL,
  x DECIMAL(10,2) NOT NULL,
  y DECIMAL(10,2) NOT NULL,
  width DECIMAL(10,2) NOT NULL,
  height DECIMAL(10,2) NOT NULL,
  z_index INT NOT NULL DEFAULT 0,
  rotation DECIMAL(8,2) NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  style_json LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_ce_template_layout_sort (template_id, z_index),
  KEY idx_ce_template_layout_field (field_id),
  KEY idx_ce_template_layout_asset (asset_id)
);

CREATE TABLE IF NOT EXISTS ce_cards (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  card_uuid VARCHAR(36) UNIQUE NOT NULL,
  game_id BIGINT NOT NULL,
  set_id BIGINT NOT NULL,
  template_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ce_cards_set_slug (set_id, slug),
  KEY idx_ce_cards_game (game_id),
  KEY idx_ce_cards_set (set_id),
  KEY idx_ce_cards_template (template_id)
);

CREATE TABLE IF NOT EXISTS ce_game_tags (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tag_uuid VARCHAR(36) UNIQUE NOT NULL,
  game_id BIGINT NOT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ce_game_tags_slug (game_id, slug),
  KEY idx_ce_game_tags_game (game_id)
);

CREATE TABLE IF NOT EXISTS ce_card_tags (
  card_id BIGINT NOT NULL,
  tag_id BIGINT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (card_id, tag_id),
  KEY idx_ce_card_tags_tag (tag_id)
);

CREATE TABLE IF NOT EXISTS ce_card_field_values (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  card_id BIGINT NOT NULL,
  field_id BIGINT NOT NULL,
  value_text LONGTEXT NULL,
  value_number DECIMAL(18,4) NULL,
  value_boolean TINYINT(1) NULL,
  value_json LONGTEXT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ce_card_field_values (card_id, field_id),
  KEY idx_ce_card_field_values_field (field_id)
);

CREATE TABLE IF NOT EXISTS ce_assets (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asset_uuid VARCHAR(36) UNIQUE NOT NULL,
  game_id BIGINT NOT NULL,
  asset_kind VARCHAR(32) NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  extension VARCHAR(16) NOT NULL,
  relative_path VARCHAR(500) NOT NULL,
  width INT NULL,
  height INT NULL,
  file_size BIGINT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_ce_assets_game_kind (game_id, asset_kind)
);
