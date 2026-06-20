<?php

class CardAuthoringDB {
    private $conn;

    public function __construct($conn) {
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        $this->conn = $conn;
        $this->ensureSchema();
    }

    private function ensureSchema() {
        static $checked = false;
        if ($checked) return;

        $schemaPath = __DIR__ . "/card_authoring_schema.sql";
        if (!file_exists($schemaPath)) {
            throw new Exception("Missing card authoring schema");
        }

        $sql = file_get_contents($schemaPath);
        foreach (explode(";", $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === "") continue;
            if (!mysqli_query($this->conn, $statement)) {
                throw new Exception("Schema setup failed: " . mysqli_error($this->conn));
            }
        }
        $checked = true;
    }

    public static function slugify($value) {
        $slug = strtolower(trim((string)$value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug !== '' ? $slug : 'item';
    }

    public static function uuidv4() {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function now() {
        return date('Y-m-d H:i:s');
    }

    private function bindAndExecute($stmt, $types = "", $params = []) {
        if (!$stmt) throw new Exception("Prepare failed: " . mysqli_error($this->conn));
        if ($types !== "") {
            $refs = [];
            $refs[] = $types;
            foreach ($params as $key => $value) {
                $refs[] = &$params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new Exception($error);
        }
        return $stmt;
    }

    private function all($sql, $types = "", $params = []) {
        $stmt = $this->bindAndExecute(mysqli_prepare($this->conn, $sql), $types, $params);
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    private function one($sql, $types = "", $params = []) {
        $rows = $this->all($sql, $types, $params);
        return count($rows) > 0 ? $rows[0] : null;
    }

    private function execute($sql, $types = "", $params = []) {
        $stmt = $this->bindAndExecute(mysqli_prepare($this->conn, $sql), $types, $params);
        mysqli_stmt_close($stmt);
        return true;
    }

    private function jsonOrNull($value) {
        if ($value === null || $value === "") return null;
        if (is_string($value)) {
            json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $value : json_encode($value);
        }
        return json_encode($value);
    }

    private function decodeJsonFields(&$row, $fields) {
        foreach ($fields as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = $row[$field] ? json_decode($row[$field], true) : null;
            }
        }
    }

    private function normalizeGame($row) {
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        return $row;
    }

    private function normalizeSet($row) {
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['game_id'] = (int)$row['game_id'];
        return $row;
    }

    private function normalizeTemplate($row) {
        if (!$row) return null;
        foreach (['id', 'game_id', 'canvas_width', 'canvas_height', 'safe_area_padding'] as $key) {
            $row[$key] = isset($row[$key]) ? (int)$row[$key] : null;
        }
        $row['canvas_background_asset_id'] = $row['canvas_background_asset_id'] ? (int)$row['canvas_background_asset_id'] : null;
        return $row;
    }

    private function normalizeField($row) {
        $row['id'] = (int)$row['id'];
        $row['template_id'] = (int)$row['template_id'];
        $row['sort_order'] = (int)$row['sort_order'];
        $this->decodeJsonFields($row, ['settings_json']);
        return $row;
    }

    private function normalizeLayoutElement($row) {
        foreach (['id', 'template_id', 'z_index'] as $key) $row[$key] = (int)$row[$key];
        $row['field_id'] = $row['field_id'] ? (int)$row['field_id'] : null;
        foreach (['x', 'y', 'width', 'height', 'rotation'] as $key) $row[$key] = (float)$row[$key];
        $row['is_visible'] = (bool)$row['is_visible'];
        $this->decodeJsonFields($row, ['style_json']);
        return $row;
    }

    private function normalizeCard($row) {
        if (!$row) return null;
        foreach (['id', 'game_id', 'set_id', 'template_id'] as $key) $row[$key] = (int)$row[$key];
        return $row;
    }

    private function normalizeValue($row) {
        $row['id'] = (int)$row['id'];
        $row['card_id'] = (int)$row['card_id'];
        $row['field_id'] = (int)$row['field_id'];
        $row['value_number'] = $row['value_number'] === null ? null : (float)$row['value_number'];
        $row['value_boolean'] = $row['value_boolean'] === null ? null : (bool)$row['value_boolean'];
        $this->decodeJsonFields($row, ['value_json']);
        return $row;
    }

    private function normalizeAsset($row) {
        if (!$row) return null;
        foreach (['id', 'game_id'] as $key) $row[$key] = (int)$row[$key];
        foreach (['width', 'height', 'file_size'] as $key) $row[$key] = $row[$key] === null ? null : (int)$row[$key];
        $row['url'] = '../' . $row['relative_path'];
        return $row;
    }

    public function createGame($input) {
        $name = trim($input['name'] ?? '');
        if ($name === '') throw new Exception("Game name is required");
        $slug = self::slugify($input['slug'] ?? $name);
        $description = $input['description'] ?? null;
        $uuid = self::uuidv4();
        $now = $this->now();
        $this->execute(
            "INSERT INTO ce_games (game_uuid, name, slug, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
            "ssssss",
            [$uuid, $name, $slug, $description, $now, $now]
        );
        return $this->getGame((int)mysqli_insert_id($this->conn));
    }

    public function listGames() {
        return array_map([$this, 'normalizeGame'], $this->all("SELECT * FROM ce_games ORDER BY name ASC"));
    }

    public function getGame($id) {
        $row = $this->one("SELECT * FROM ce_games WHERE id = ?", "i", [(int)$id]);
        if (!$row) throw new Exception("Game not found");
        return $this->normalizeGame($row);
    }

    public function updateGame($id, $input) {
        $game = $this->getGame($id);
        $name = trim($input['name'] ?? $game['name']);
        if ($name === '') throw new Exception("Game name is required");
        $slug = self::slugify($input['slug'] ?? $game['slug']);
        $description = array_key_exists('description', $input) ? $input['description'] : $game['description'];
        $now = $this->now();
        $this->execute(
            "UPDATE ce_games SET name = ?, slug = ?, description = ?, updated_at = ? WHERE id = ?",
            "ssssi",
            [$name, $slug, $description, $now, (int)$id]
        );
        return $this->getGame($id);
    }

    public function createSet($input) {
        $gameId = (int)($input['gameId'] ?? $input['game_id'] ?? 0);
        $this->getGame($gameId);
        $name = trim($input['name'] ?? '');
        if ($name === '') throw new Exception("Set name is required");
        $slug = self::slugify($input['slug'] ?? $name);
        $description = $input['description'] ?? null;
        $uuid = self::uuidv4();
        $now = $this->now();
        $this->execute(
            "INSERT INTO ce_sets (set_uuid, game_id, name, slug, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "sisssss",
            [$uuid, $gameId, $name, $slug, $description, $now, $now]
        );
        return $this->getSet((int)mysqli_insert_id($this->conn));
    }

    public function listSets($gameId) {
        $this->getGame($gameId);
        return array_map([$this, 'normalizeSet'], $this->all("SELECT * FROM ce_sets WHERE game_id = ? ORDER BY name ASC", "i", [(int)$gameId]));
    }

    public function getSet($id) {
        $row = $this->one("SELECT * FROM ce_sets WHERE id = ?", "i", [(int)$id]);
        if (!$row) throw new Exception("Set not found");
        return $this->normalizeSet($row);
    }

    public function updateSet($id, $input) {
        $set = $this->getSet($id);
        $name = trim($input['name'] ?? $set['name']);
        if ($name === '') throw new Exception("Set name is required");
        $slug = self::slugify($input['slug'] ?? $set['slug']);
        $description = array_key_exists('description', $input) ? $input['description'] : $set['description'];
        $now = $this->now();
        $this->execute("UPDATE ce_sets SET name = ?, slug = ?, description = ?, updated_at = ? WHERE id = ?", "ssssi", [$name, $slug, $description, $now, (int)$id]);
        return $this->getSet($id);
    }

    public function createTemplate($input) {
        $gameId = (int)($input['gameId'] ?? $input['game_id'] ?? 0);
        $this->getGame($gameId);
        $name = trim($input['name'] ?? '');
        if ($name === '') throw new Exception("Template name is required");
        $slug = self::slugify($input['slug'] ?? $name);
        $description = $input['description'] ?? null;
        $width = (int)($input['canvasWidth'] ?? $input['canvas_width'] ?? 750);
        $height = (int)($input['canvasHeight'] ?? $input['canvas_height'] ?? 1050);
        $bgColor = $input['canvasBackgroundColor'] ?? $input['canvas_background_color'] ?? '#ffffff';
        $safe = (int)($input['safeAreaPadding'] ?? $input['safe_area_padding'] ?? 40);
        $bgAsset = isset($input['canvasBackgroundAssetId']) && $input['canvasBackgroundAssetId'] !== '' ? (int)$input['canvasBackgroundAssetId'] : null;
        $uuid = self::uuidv4();
        $now = $this->now();
        $this->execute(
            "INSERT INTO ce_templates (template_uuid, game_id, name, slug, description, canvas_width, canvas_height, canvas_background_color, canvas_background_asset_id, safe_area_padding, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sisssiisiiss",
            [$uuid, $gameId, $name, $slug, $description, $width, $height, $bgColor, $bgAsset, $safe, $now, $now]
        );
        return $this->getTemplate((int)mysqli_insert_id($this->conn));
    }

    public function listTemplates($gameId) {
        $this->getGame($gameId);
        $rows = $this->all("SELECT * FROM ce_templates WHERE game_id = ? ORDER BY name ASC", "i", [(int)$gameId]);
        return array_map([$this, 'normalizeTemplate'], $rows);
    }

    public function getTemplate($id) {
        $row = $this->one("SELECT * FROM ce_templates WHERE id = ?", "i", [(int)$id]);
        if (!$row) throw new Exception("Template not found");
        $template = $this->normalizeTemplate($row);
        $template['fields'] = array_map([$this, 'normalizeField'], $this->all("SELECT * FROM ce_template_fields WHERE template_id = ? ORDER BY sort_order ASC, id ASC", "i", [(int)$id]));
        $template['layout'] = array_map([$this, 'normalizeLayoutElement'], $this->all("SELECT * FROM ce_template_layout_elements WHERE template_id = ? ORDER BY z_index ASC, id ASC", "i", [(int)$id]));
        return $template;
    }

    public function updateTemplate($id, $input) {
        $template = $this->getTemplate($id);
        $name = trim($input['name'] ?? $template['name']);
        if ($name === '') throw new Exception("Template name is required");
        $slug = self::slugify($input['slug'] ?? $template['slug']);
        $description = array_key_exists('description', $input) ? $input['description'] : $template['description'];
        $width = (int)($input['canvasWidth'] ?? $input['canvas_width'] ?? $template['canvas_width']);
        $height = (int)($input['canvasHeight'] ?? $input['canvas_height'] ?? $template['canvas_height']);
        $bgColor = $input['canvasBackgroundColor'] ?? $input['canvas_background_color'] ?? $template['canvas_background_color'];
        $safe = (int)($input['safeAreaPadding'] ?? $input['safe_area_padding'] ?? $template['safe_area_padding']);
        $bgAsset = array_key_exists('canvasBackgroundAssetId', $input) ? ($input['canvasBackgroundAssetId'] === '' ? null : (int)$input['canvasBackgroundAssetId']) : $template['canvas_background_asset_id'];
        $now = $this->now();
        $this->execute(
            "UPDATE ce_templates SET name = ?, slug = ?, description = ?, canvas_width = ?, canvas_height = ?, canvas_background_color = ?, canvas_background_asset_id = ?, safe_area_padding = ?, updated_at = ? WHERE id = ?",
            "sssiisiisi",
            [$name, $slug, $description, $width, $height, $bgColor, $bgAsset, $safe, $now, (int)$id]
        );
        return $this->getTemplate($id);
    }

    public function saveTemplateFields($templateId, $fields) {
        $this->getTemplate($templateId);
        if (!is_array($fields)) throw new Exception("Fields must be an array");
        $validTypes = ['text', 'longtext', 'number', 'boolean', 'select', 'multiselect', 'image'];
        $now = $this->now();
        mysqli_query($this->conn, "START TRANSACTION");
        try {
            $existing = $this->all("SELECT id FROM ce_template_fields WHERE template_id = ?", "i", [(int)$templateId]);
            $existingIds = array_map(function($row) { return (int)$row['id']; }, $existing);
            $keptIds = [];
            foreach ($fields as $index => $field) {
                $label = trim($field['label'] ?? '');
                $key = self::slugify($field['field_key'] ?? $field['fieldKey'] ?? $label);
                $key = str_replace('-', '_', $key);
                $type = $field['field_type'] ?? $field['fieldType'] ?? 'text';
                if ($label === '') throw new Exception("Field label is required");
                if (!in_array($type, $validTypes)) throw new Exception("Invalid field type");
                $helpText = $field['help_text'] ?? $field['helpText'] ?? null;
                $default = $field['default_value'] ?? $field['defaultValue'] ?? null;
                $sort = (int)($field['sort_order'] ?? $field['sortOrder'] ?? $index);
                $settingsJson = $this->jsonOrNull($field['settings_json'] ?? $field['settingsJson'] ?? null);
                $id = isset($field['id']) && $field['id'] ? (int)$field['id'] : 0;
                if ($id > 0 && in_array($id, $existingIds)) {
                    $this->execute(
                        "UPDATE ce_template_fields SET field_key = ?, label = ?, field_type = ?, help_text = ?, default_value = ?, sort_order = ?, settings_json = ?, updated_at = ? WHERE id = ? AND template_id = ?",
                        "sssssissii",
                        [$key, $label, $type, $helpText, $default, $sort, $settingsJson, $now, $id, (int)$templateId]
                    );
                    $keptIds[] = $id;
                } else {
                    $uuid = self::uuidv4();
                    $this->execute(
                        "INSERT INTO ce_template_fields (field_uuid, template_id, field_key, label, field_type, help_text, default_value, sort_order, settings_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        "sisssssisss",
                        [$uuid, (int)$templateId, $key, $label, $type, $helpText, $default, $sort, $settingsJson, $now, $now]
                    );
                    $keptIds[] = (int)mysqli_insert_id($this->conn);
                }
            }
            foreach ($existingIds as $existingId) {
                if (!in_array($existingId, $keptIds)) {
                    $this->execute("DELETE FROM ce_template_layout_elements WHERE field_id = ?", "i", [$existingId]);
                    $this->execute("DELETE FROM ce_card_field_values WHERE field_id = ?", "i", [$existingId]);
                    $this->execute("DELETE FROM ce_template_fields WHERE id = ?", "i", [$existingId]);
                }
            }
            $this->execute("UPDATE ce_templates SET updated_at = ? WHERE id = ?", "si", [$now, (int)$templateId]);
            mysqli_query($this->conn, "COMMIT");
            return $this->getTemplate($templateId);
        } catch (Exception $e) {
            mysqli_query($this->conn, "ROLLBACK");
            throw $e;
        }
    }

    public function saveTemplateLayout($templateId, $elements) {
        $this->getTemplate($templateId);
        if (!is_array($elements)) throw new Exception("Layout must be an array");
        $now = $this->now();
        mysqli_query($this->conn, "START TRANSACTION");
        try {
            $this->execute("DELETE FROM ce_template_layout_elements WHERE template_id = ?", "i", [(int)$templateId]);
            foreach ($elements as $index => $element) {
                $type = $element['element_type'] ?? $element['elementType'] ?? 'field';
                if ($type !== 'field') throw new Exception("Only field elements are supported in v1");
                $fieldId = isset($element['field_id']) ? (int)$element['field_id'] : (isset($element['fieldId']) ? (int)$element['fieldId'] : null);
                if (!$fieldId) throw new Exception("Field element missing field id");
                $field = $this->one("SELECT id FROM ce_template_fields WHERE id = ? AND template_id = ?", "ii", [$fieldId, (int)$templateId]);
                if (!$field) throw new Exception("Layout field does not belong to template");
                $uuid = self::uuidv4();
                $styleJson = $this->jsonOrNull($element['style_json'] ?? $element['styleJson'] ?? null);
                $this->execute(
                    "INSERT INTO ce_template_layout_elements (element_uuid, template_id, element_type, field_id, x, y, width, height, z_index, rotation, is_visible, style_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    "sisiddddidisss",
                    [
                        $uuid,
                        (int)$templateId,
                        $type,
                        $fieldId,
                        (float)($element['x'] ?? 40),
                        (float)($element['y'] ?? 40),
                        (float)($element['width'] ?? 200),
                        (float)($element['height'] ?? 60),
                        (int)($element['z_index'] ?? $element['zIndex'] ?? $index),
                        (float)($element['rotation'] ?? 0),
                        !empty($element['is_visible']) || !array_key_exists('is_visible', $element) ? 1 : 0,
                        $styleJson,
                        $now,
                        $now
                    ]
                );
            }
            $this->execute("UPDATE ce_templates SET updated_at = ? WHERE id = ?", "si", [$now, (int)$templateId]);
            mysqli_query($this->conn, "COMMIT");
            return $this->getTemplate($templateId);
        } catch (Exception $e) {
            mysqli_query($this->conn, "ROLLBACK");
            throw $e;
        }
    }

    private function assertSetAndTemplate($gameId, $setId, $templateId) {
        $set = $this->getSet($setId);
        if ((int)$set['game_id'] !== (int)$gameId) throw new Exception("Set does not belong to game");
        $template = $this->getTemplate($templateId);
        if ((int)$template['game_id'] !== (int)$gameId) throw new Exception("Template does not belong to game");
    }

    public function createCard($input) {
        $gameId = (int)($input['gameId'] ?? $input['game_id'] ?? 0);
        $setId = (int)($input['setId'] ?? $input['set_id'] ?? 0);
        $templateId = (int)($input['templateId'] ?? $input['template_id'] ?? 0);
        $this->assertSetAndTemplate($gameId, $setId, $templateId);
        $name = trim($input['name'] ?? '');
        if ($name === '') throw new Exception("Card name is required");
        $slug = self::slugify($input['slug'] ?? $name);
        $uuid = self::uuidv4();
        $now = $this->now();
        $this->execute(
            "INSERT INTO ce_cards (card_uuid, game_id, set_id, template_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            "siiissss",
            [$uuid, $gameId, $setId, $templateId, $name, $slug, $now, $now]
        );
        return $this->getCard((int)mysqli_insert_id($this->conn));
    }

    public function listCards($setId) {
        $this->getSet($setId);
        return array_map([$this, 'normalizeCard'], $this->all("SELECT * FROM ce_cards WHERE set_id = ? ORDER BY name ASC", "i", [(int)$setId]));
    }

    public function getCard($id) {
        $row = $this->one("SELECT * FROM ce_cards WHERE id = ?", "i", [(int)$id]);
        if (!$row) throw new Exception("Card not found");
        $card = $this->normalizeCard($row);
        $card['template'] = $this->getTemplate($card['template_id']);
        $card['values'] = array_map([$this, 'normalizeValue'], $this->all("SELECT * FROM ce_card_field_values WHERE card_id = ?", "i", [(int)$id]));
        return $card;
    }

    public function updateCard($id, $input) {
        $card = $this->getCard($id);
        $name = trim($input['name'] ?? $card['name']);
        if ($name === '') throw new Exception("Card name is required");
        $slug = self::slugify($input['slug'] ?? $card['slug']);
        $now = $this->now();
        $this->execute("UPDATE ce_cards SET name = ?, slug = ?, updated_at = ? WHERE id = ?", "sssi", [$name, $slug, $now, (int)$id]);
        return $this->getCard($id);
    }

    public function saveCardFieldValues($cardId, $values) {
        $card = $this->getCard($cardId);
        if (!is_array($values)) throw new Exception("Values must be an array");
        $fields = [];
        foreach ($card['template']['fields'] as $field) {
            $fields[(int)$field['id']] = $field;
        }
        $now = $this->now();
        mysqli_query($this->conn, "START TRANSACTION");
        try {
            foreach ($values as $item) {
                $fieldId = (int)($item['fieldId'] ?? $item['field_id'] ?? 0);
                if (!isset($fields[$fieldId])) throw new Exception("Value field does not belong to card template");
                $type = $fields[$fieldId]['field_type'];
                $raw = $item['value'] ?? null;
                $text = null;
                $number = null;
                $boolean = null;
                $json = null;
                if (in_array($type, ['text', 'longtext', 'select', 'image'])) {
                    $text = $raw === null ? null : (string)$raw;
                } elseif ($type === 'number') {
                    $number = ($raw === '' || $raw === null) ? null : (float)$raw;
                } elseif ($type === 'boolean') {
                    $boolean = $raw ? 1 : 0;
                } elseif ($type === 'multiselect') {
                    $json = json_encode(is_array($raw) ? array_values($raw) : []);
                }
                $this->execute(
                    "INSERT INTO ce_card_field_values (card_id, field_id, value_text, value_number, value_boolean, value_json, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), value_number = VALUES(value_number), value_boolean = VALUES(value_boolean), value_json = VALUES(value_json), updated_at = VALUES(updated_at)",
                    "iisdiss",
                    [(int)$cardId, $fieldId, $text, $number, $boolean, $json, $now]
                );
            }
            $this->execute("UPDATE ce_cards SET updated_at = ? WHERE id = ?", "si", [$now, (int)$cardId]);
            mysqli_query($this->conn, "COMMIT");
            return $this->getCard($cardId);
        } catch (Exception $e) {
            mysqli_query($this->conn, "ROLLBACK");
            throw $e;
        }
    }

    public function listAssets($gameId) {
        $this->getGame($gameId);
        return array_map([$this, 'normalizeAsset'], $this->all("SELECT * FROM ce_assets WHERE game_id = ? ORDER BY created_at DESC, id DESC", "i", [(int)$gameId]));
    }

    public function createAsset($input) {
        $gameId = (int)$input['gameId'];
        $this->getGame($gameId);
        $uuid = $input['assetUuid'] ?? self::uuidv4();
        $kind = $input['assetKind'] ?? 'image';
        $now = $this->now();
        $this->execute(
            "INSERT INTO ce_assets (asset_uuid, game_id, asset_kind, original_filename, mime_type, extension, relative_path, width, height, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sisssssiiis",
            [
                $uuid,
                $gameId,
                $kind,
                $input['originalFilename'],
                $input['mimeType'],
                $input['extension'],
                $input['relativePath'],
                $input['width'],
                $input['height'],
                $input['fileSize'],
                $now
            ]
        );
        return $this->normalizeAsset($this->one("SELECT * FROM ce_assets WHERE id = ?", "i", [(int)mysqli_insert_id($this->conn)]));
    }

    public function exportGame($gameId) {
        $game = $this->getGame($gameId);
        $sets = $this->listSets($gameId);
        $templates = [];
        foreach ($this->listTemplates($gameId) as $template) $templates[] = $this->getTemplate($template['id']);
        $cards = array_map([$this, 'normalizeCard'], $this->all("SELECT * FROM ce_cards WHERE game_id = ? ORDER BY set_id ASC, name ASC", "i", [(int)$gameId]));
        foreach ($cards as &$card) {
            $card['values'] = array_map([$this, 'normalizeValue'], $this->all("SELECT * FROM ce_card_field_values WHERE card_id = ?", "i", [$card['id']]));
        }
        return [
            'game' => $game,
            'sets' => $sets,
            'templates' => $templates,
            'cards' => $cards,
            'assets' => $this->listAssets($gameId)
        ];
    }

    public function exportSet($setId) {
        $set = $this->getSet($setId);
        $export = $this->exportGame($set['game_id']);
        $export['sets'] = [$set];
        $export['cards'] = array_values(array_filter($export['cards'], function($card) use ($setId) {
            return (int)$card['set_id'] === (int)$setId;
        }));
        return $export;
    }
}

?>
