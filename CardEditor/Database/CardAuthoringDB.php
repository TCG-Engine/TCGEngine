<?php

include_once(__DIR__ . '/../../AccountFiles/AccountSessionAPI.php');

class CardAuthoringDB {
    const ASSET_TYPE_CARD_EDITOR_GAME = 9001;

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
        $this->ensureColumn('ce_template_layout_elements', 'asset_id', 'BIGINT NULL AFTER field_id');
        $this->ensureIndex('ce_template_layout_elements', 'idx_ce_template_layout_asset', 'asset_id');
        $this->ensureIndex('ce_game_tags', 'idx_ce_game_tags_game', 'game_id');
        $this->ensureIndex('ce_card_tags', 'idx_ce_card_tags_tag', 'tag_id');
        $this->ensureIndex('ce_game_enums', 'idx_ce_game_enums_game', 'game_id');
        $this->ensureIndex('ce_game_enum_options', 'idx_ce_game_enum_options_enum', 'enum_id');
        $this->ensureIndex('ce_game_enum_options', 'idx_ce_game_enum_options_asset', 'asset_id');
        $checked = true;
    }

    private function ensureColumn($table, $column, $definition) {
        $table = mysqli_real_escape_string($this->conn, $table);
        $column = mysqli_real_escape_string($this->conn, $column);
        $result = mysqli_query($this->conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($result && mysqli_num_rows($result) === 0) {
            if (!mysqli_query($this->conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
                throw new Exception("Schema migration failed: " . mysqli_error($this->conn));
            }
        }
        if ($result) mysqli_free_result($result);
    }

    private function ensureIndex($table, $index, $column) {
        $table = mysqli_real_escape_string($this->conn, $table);
        $index = mysqli_real_escape_string($this->conn, $index);
        $result = mysqli_query($this->conn, "SHOW INDEX FROM `$table` WHERE Key_name = '$index'");
        if ($result && mysqli_num_rows($result) === 0) {
            if (!mysqli_query($this->conn, "ALTER TABLE `$table` ADD KEY `$index` (`$column`)")) {
                throw new Exception("Schema migration failed: " . mysqli_error($this->conn));
            }
        }
        if ($result) mysqli_free_result($result);
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

    private function currentUserId() {
        return IsUserLoggedIn() ? (int)LoggedInUser() : 0;
    }

    private function requireLoggedIn() {
        $userId = $this->currentUserId();
        if ($userId <= 0) throw new Exception("You must be logged in to edit CardEditor games");
        return $userId;
    }

    private function currentUserTeamId() {
        $userId = $this->currentUserId();
        if ($userId <= 0) return 0;
        $row = $this->one("SELECT teamID FROM users WHERE usersId = ?", "i", [$userId]);
        return $row && $row['teamID'] !== null ? (int)$row['teamID'] : 0;
    }

    private function visibilityToInt($visibility) {
        if (is_numeric($visibility)) return (int)$visibility;
        $visibility = strtolower(trim((string)$visibility));
        if ($visibility === 'public') return 2;
        if ($visibility === 'link only' || $visibility === 'link_only' || $visibility === 'link') return 1;
        if ($visibility === 'team') {
            $teamId = $this->currentUserTeamId();
            return $teamId > 0 ? 1000 + $teamId : 0;
        }
        return 0;
    }

    private function visibilityFromInt($visibility) {
        $visibility = (int)$visibility;
        if ($visibility === 2) return 'public';
        if ($visibility === 1) return 'link only';
        if ($visibility >= 1000) return 'team';
        return 'private';
    }

    private function ownershipForGame($gameId) {
        return $this->one(
            "SELECT * FROM ownership WHERE assetType = ? AND assetIdentifier = ?",
            "ii",
            [self::ASSET_TYPE_CARD_EDITOR_GAME, (int)$gameId]
        );
    }

    private function canViewGameRow($game) {
        $ownership = $this->ownershipForGame($game['id']);
        if (!$ownership) return $this->currentUserId() > 0;
        $visibility = (int)($ownership['assetVisibility'] ?? 0);
        $userId = $this->currentUserId();
        if ($userId > 0 && (int)$ownership['assetOwner'] === $userId) return true;
        if ($visibility === 1 || $visibility === 2) return true;
        if ($visibility >= 1000 && $userId > 0) return $this->currentUserTeamId() === ($visibility - 1000);
        return false;
    }

    private function canEditGameRow($game) {
        $ownership = $this->ownershipForGame($game['id']);
        $userId = $this->currentUserId();
        if ($userId <= 0) return false;
        if (!$ownership) return true;
        if ((int)$ownership['assetOwner'] === $userId) return true;
        $visibility = (int)($ownership['assetVisibility'] ?? 0);
        return $visibility >= 1000 && $this->currentUserTeamId() === ($visibility - 1000);
    }

    private function assertCanViewGame($gameId) {
        $game = $this->rawGame($gameId);
        if (!$game || !$this->canViewGameRow($game)) throw new Exception("You do not have access to this game");
        return $game;
    }

    private function assertCanEditGame($gameId) {
        $game = $this->rawGame($gameId);
        if (!$game || !$this->canEditGameRow($game)) throw new Exception("You do not have permission to edit this game");
        return $game;
    }

    private function assertFresh($table, $id, $expectedUpdatedAt) {
        if ($expectedUpdatedAt === null || $expectedUpdatedAt === '') return;
        $allowed = ['ce_games', 'ce_sets', 'ce_templates', 'ce_cards'];
        if (!in_array($table, $allowed)) return;
        $row = $this->one("SELECT updated_at FROM `$table` WHERE id = ?", "i", [(int)$id]);
        if ($row && (string)$row['updated_at'] !== (string)$expectedUpdatedAt) {
            throw new Exception("This record changed elsewhere. Refresh before saving.");
        }
    }

    private function saveGameOwnership($gameId, $name, $visibility = null) {
        $userId = $this->requireLoggedIn();
        $existing = $this->ownershipForGame($gameId);
        if ($existing) {
            $visibilityInt = $visibility === null ? (int)($existing['assetVisibility'] ?? 0) : $this->visibilityToInt($visibility);
            $this->execute(
                "UPDATE ownership SET assetName = ?, assetVisibility = ? WHERE assetType = ? AND assetIdentifier = ?",
                "siii",
                [$name, $visibilityInt, self::ASSET_TYPE_CARD_EDITOR_GAME, (int)$gameId]
            );
            return;
        }
        $visibilityInt = $visibility === null ? 0 : $this->visibilityToInt($visibility);
        $this->execute(
            "INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus, assetName, assetVisibility) VALUES (?, ?, ?, 1, ?, ?)",
            "iiisi",
            [self::ASSET_TYPE_CARD_EDITOR_GAME, (int)$gameId, $userId, $name, $visibilityInt]
        );
    }

    private function rawGame($id) {
        return $this->one("SELECT * FROM ce_games WHERE id = ?", "i", [(int)$id]);
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

    private function styleJsonOrNull($value) {
        if ($value === null || $value === "") return null;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                return json_encode($value);
            }
        }
        if (is_object($value)) $value = (array)$value;
        if (is_array($value)) {
            if (array_key_exists('behindTemplate', $value)) {
                $raw = $value['behindTemplate'];
                $value['behindTemplate'] = $raw === true || $raw === 1 || $raw === '1' || $raw === 'true' || $raw === 'on' || $raw === 'yes';
                if (!$value['behindTemplate']) unset($value['behindTemplate']);
            }
            return empty($value) ? null : json_encode($value);
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
        $ownership = $this->ownershipForGame($row['id']);
        $row['ownership'] = [
            'assetType' => self::ASSET_TYPE_CARD_EDITOR_GAME,
            'ownerId' => $ownership ? (int)$ownership['assetOwner'] : null,
            'visibility' => $ownership ? $this->visibilityFromInt($ownership['assetVisibility'] ?? 0) : 'private',
            'visibilityValue' => $ownership ? (int)($ownership['assetVisibility'] ?? 0) : 0,
        ];
        $row['can_edit'] = $this->canEditGameRow($row);
        $row['can_view'] = $this->canViewGameRow($row);
        $row['tags'] = $this->listGameTagsForVisibleRow($row);
        $row['enums'] = $this->listGameEnumsForVisibleRow($row);
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
        $row['asset_id'] = $row['asset_id'] ? (int)$row['asset_id'] : null;
        foreach (['x', 'y', 'width', 'height', 'rotation'] as $key) $row[$key] = (float)$row[$key];
        $row['is_visible'] = (bool)$row['is_visible'];
        $this->decodeJsonFields($row, ['style_json']);
        if ($row['style_json'] === null || $row['style_json'] === []) $row['style_json'] = new stdClass();
        return $row;
    }

    private function normalizeCard($row) {
        if (!$row) return null;
        foreach (['id', 'game_id', 'set_id', 'template_id'] as $key) $row[$key] = (int)$row[$key];
        return $row;
    }

    private function normalizeTag($row) {
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['game_id'] = (int)$row['game_id'];
        return $row;
    }

    private function normalizeEnum($row) {
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['game_id'] = (int)$row['game_id'];
        $row['options'] = [];
        return $row;
    }

    private function normalizeEnumOption($row) {
        if (!$row) return null;
        $row['id'] = (int)$row['id'];
        $row['enum_id'] = (int)$row['enum_id'];
        $row['asset_id'] = $row['asset_id'] ? (int)$row['asset_id'] : null;
        $row['sort_order'] = (int)$row['sort_order'];
        if (isset($row['asset_relative_path'])) {
            $row['asset'] = $row['asset_id'] ? [
                'id' => $row['asset_id'],
                'original_filename' => $row['asset_original_filename'],
                'url' => '../' . $row['asset_relative_path']
            ] : null;
            unset($row['asset_relative_path'], $row['asset_original_filename']);
        }
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
        $this->requireLoggedIn();
        $name = trim($input['name'] ?? '');
        if ($name === '') throw new Exception("Game name is required");
        $slug = self::slugify($input['slug'] ?? $name);
        $description = $input['description'] ?? null;
        $visibility = $input['visibility'] ?? 'private';
        $uuid = self::uuidv4();
        $now = $this->now();
        $this->execute(
            "INSERT INTO ce_games (game_uuid, name, slug, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
            "ssssss",
            [$uuid, $name, $slug, $description, $now, $now]
        );
        $gameId = (int)mysqli_insert_id($this->conn);
        $this->saveGameOwnership($gameId, $name, $visibility);
        if (array_key_exists('tags', $input)) {
            $this->syncGameTags($gameId, is_array($input['tags']) ? $input['tags'] : []);
        }
        if (array_key_exists('enums', $input)) {
            $this->syncGameEnums($gameId, is_array($input['enums']) ? $input['enums'] : []);
        }
        return $this->getGame($gameId);
    }

    public function listGames() {
        $rows = $this->all("SELECT * FROM ce_games ORDER BY name ASC");
        $visible = array_filter($rows, function($row) { return $this->canViewGameRow($row); });
        return array_map([$this, 'normalizeGame'], array_values($visible));
    }

    public function getGame($id) {
        $row = $this->assertCanViewGame($id);
        if (!$row) throw new Exception("Game not found");
        $game = $this->normalizeGame($row);
        $game['tags'] = $this->listGameTags($game['id']);
        $game['enums'] = $this->listGameEnums($game['id']);
        return $game;
    }

    public function updateGame($id, $input) {
        $game = $this->assertCanEditGame($id);
        $this->assertFresh('ce_games', $id, $input['expectedUpdatedAt'] ?? null);
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
        $this->saveGameOwnership($id, $name, $input['visibility'] ?? null);
        if (array_key_exists('tags', $input)) {
            $this->syncGameTags($id, is_array($input['tags']) ? $input['tags'] : []);
        }
        if (array_key_exists('enums', $input)) {
            $this->syncGameEnums($id, is_array($input['enums']) ? $input['enums'] : []);
        }
        return $this->getGame($id);
    }

    public function listGameTags($gameId) {
        $this->assertCanViewGame($gameId);
        return $this->listGameTagsForVisibleRow(['id' => (int)$gameId]);
    }

    private function listGameTagsForVisibleRow($game) {
        $rows = $this->all("SELECT * FROM ce_game_tags WHERE game_id = ? ORDER BY name ASC, id ASC", "i", [(int)$game['id']]);
        return array_map([$this, 'normalizeTag'], $rows);
    }

    public function listGameEnums($gameId) {
        $this->assertCanViewGame($gameId);
        return $this->listGameEnumsForVisibleRow(['id' => (int)$gameId]);
    }

    private function listGameEnumsForVisibleRow($game) {
        $enums = array_map(
            [$this, 'normalizeEnum'],
            $this->all("SELECT * FROM ce_game_enums WHERE game_id = ? ORDER BY name ASC, id ASC", "i", [(int)$game['id']])
        );
        foreach ($enums as &$enum) {
            $enum['options'] = array_map(
                [$this, 'normalizeEnumOption'],
                $this->all(
                    "SELECT o.*, a.relative_path AS asset_relative_path, a.original_filename AS asset_original_filename FROM ce_game_enum_options o LEFT JOIN ce_assets a ON a.id = o.asset_id WHERE o.enum_id = ? ORDER BY o.sort_order ASC, o.id ASC",
                    "i",
                    [(int)$enum['id']]
                )
            );
        }
        return $enums;
    }

    private function syncGameTags($gameId, $tags) {
        $this->assertCanEditGame($gameId);
        $existing = $this->all("SELECT id FROM ce_game_tags WHERE game_id = ?", "i", [(int)$gameId]);
        $existingIds = array_map(function($row) { return (int)$row['id']; }, $existing);
        $incomingIds = [];
        foreach ($tags as $tag) {
            $id = isset($tag['id']) && $tag['id'] ? (int)$tag['id'] : 0;
            if ($id > 0 && in_array($id, $existingIds)) $incomingIds[] = $id;
        }
        foreach ($existingIds as $existingId) {
            if (!in_array($existingId, $incomingIds)) {
                $this->execute("DELETE FROM ce_card_tags WHERE tag_id = ?", "i", [$existingId]);
                $this->execute("DELETE FROM ce_game_tags WHERE id = ? AND game_id = ?", "ii", [$existingId, (int)$gameId]);
            }
        }
        $keptIds = [];
        $usedSlugs = [];
        $now = $this->now();
        foreach ($tags as $tag) {
            $name = trim($tag['name'] ?? '');
            if ($name === '') continue;
            $slugBase = self::slugify($tag['slug'] ?? $name);
            $slug = $slugBase;
            $suffix = 2;
            while (in_array($slug, $usedSlugs)) {
                $slug = $slugBase . '-' . $suffix;
                $suffix++;
            }
            $usedSlugs[] = $slug;
            $id = isset($tag['id']) && $tag['id'] ? (int)$tag['id'] : 0;
            if ($id > 0 && in_array($id, $existingIds)) {
                $this->execute(
                    "UPDATE ce_game_tags SET name = ?, slug = ?, updated_at = ? WHERE id = ? AND game_id = ?",
                    "sssii",
                    [$name, $slug, $now, $id, (int)$gameId]
                );
                $keptIds[] = $id;
            } else {
                $uuid = self::uuidv4();
                $this->execute(
                    "INSERT INTO ce_game_tags (tag_uuid, game_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
                    "sissss",
                    [$uuid, (int)$gameId, $name, $slug, $now, $now]
                );
                $keptIds[] = (int)mysqli_insert_id($this->conn);
            }
        }
        foreach ($existingIds as $existingId) {
            if (!in_array($existingId, $keptIds)) {
                $this->execute("DELETE FROM ce_card_tags WHERE tag_id = ?", "i", [$existingId]);
                $this->execute("DELETE FROM ce_game_tags WHERE id = ? AND game_id = ?", "ii", [$existingId, (int)$gameId]);
            }
        }
    }

    private function syncGameEnums($gameId, $enums) {
        $this->assertCanEditGame($gameId);
        $existing = $this->all("SELECT id FROM ce_game_enums WHERE game_id = ?", "i", [(int)$gameId]);
        $existingIds = array_map(function($row) { return (int)$row['id']; }, $existing);
        $incomingIds = [];
        foreach ($enums as $enum) {
            $id = isset($enum['id']) && $enum['id'] ? (int)$enum['id'] : 0;
            if ($id > 0 && in_array($id, $existingIds)) $incomingIds[] = $id;
        }
        foreach ($existingIds as $existingId) {
            if (!in_array($existingId, $incomingIds)) {
                $this->execute("DELETE FROM ce_game_enum_options WHERE enum_id = ?", "i", [$existingId]);
                $this->execute("DELETE FROM ce_game_enums WHERE id = ? AND game_id = ?", "ii", [$existingId, (int)$gameId]);
            }
        }

        $usedSlugs = [];
        $now = $this->now();
        foreach ($enums as $enum) {
            $name = trim($enum['name'] ?? '');
            if ($name === '') continue;
            $slugBase = self::slugify($enum['slug'] ?? $name);
            $slug = $slugBase;
            $suffix = 2;
            while (in_array($slug, $usedSlugs)) {
                $slug = $slugBase . '-' . $suffix;
                $suffix++;
            }
            $usedSlugs[] = $slug;
            $id = isset($enum['id']) && $enum['id'] ? (int)$enum['id'] : 0;
            if ($id > 0 && in_array($id, $existingIds)) {
                $this->execute(
                    "UPDATE ce_game_enums SET name = ?, slug = ?, updated_at = ? WHERE id = ? AND game_id = ?",
                    "sssii",
                    [$name, $slug, $now, $id, (int)$gameId]
                );
                $enumId = $id;
            } else {
                $uuid = self::uuidv4();
                $this->execute(
                    "INSERT INTO ce_game_enums (enum_uuid, game_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
                    "sissss",
                    [$uuid, (int)$gameId, $name, $slug, $now, $now]
                );
                $enumId = (int)mysqli_insert_id($this->conn);
            }
            $this->syncGameEnumOptions($gameId, $enumId, is_array($enum['options'] ?? null) ? $enum['options'] : []);
        }
    }

    private function syncGameEnumOptions($gameId, $enumId, $options) {
        $existing = $this->all("SELECT id FROM ce_game_enum_options WHERE enum_id = ?", "i", [(int)$enumId]);
        $existingIds = array_map(function($row) { return (int)$row['id']; }, $existing);
        $incomingIds = [];
        foreach ($options as $option) {
            $id = isset($option['id']) && $option['id'] ? (int)$option['id'] : 0;
            if ($id > 0 && in_array($id, $existingIds)) $incomingIds[] = $id;
        }
        foreach ($existingIds as $existingId) {
            if (!in_array($existingId, $incomingIds)) {
                $this->execute("DELETE FROM ce_game_enum_options WHERE id = ? AND enum_id = ?", "ii", [$existingId, (int)$enumId]);
            }
        }

        $usedValues = [];
        $now = $this->now();
        foreach ($options as $index => $option) {
            $label = trim($option['label'] ?? '');
            if ($label === '') continue;
            $rawValue = trim((string)($option['value'] ?? ''));
            $valueBase = self::slugify($rawValue !== '' ? $rawValue : $label);
            $value = $valueBase;
            $suffix = 2;
            while (in_array($value, $usedValues)) {
                $value = $valueBase . '-' . $suffix;
                $suffix++;
            }
            $usedValues[] = $value;
            $assetId = isset($option['assetId']) ? (int)$option['assetId'] : (isset($option['asset_id']) ? (int)$option['asset_id'] : 0);
            $assetId = $assetId > 0 ? $assetId : null;
            if ($assetId !== null) {
                $asset = $this->one("SELECT id FROM ce_assets WHERE id = ? AND game_id = ?", "ii", [$assetId, (int)$gameId]);
                if (!$asset) $assetId = null;
            }
            $sortOrder = (int)($option['sortOrder'] ?? $option['sort_order'] ?? $index);
            $id = isset($option['id']) && $option['id'] ? (int)$option['id'] : 0;
            if ($id > 0 && in_array($id, $existingIds)) {
                $this->execute(
                    "UPDATE ce_game_enum_options SET label = ?, value = ?, asset_id = ?, sort_order = ?, updated_at = ? WHERE id = ? AND enum_id = ?",
                    "ssiisii",
                    [$label, $value, $assetId, $sortOrder, $now, $id, (int)$enumId]
                );
            } else {
                $uuid = self::uuidv4();
                $this->execute(
                    "INSERT INTO ce_game_enum_options (option_uuid, enum_id, label, value, asset_id, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    "sissiiss",
                    [$uuid, (int)$enumId, $label, $value, $assetId, $sortOrder, $now, $now]
                );
            }
        }
    }

    public function createSet($input) {
        $gameId = (int)($input['gameId'] ?? $input['game_id'] ?? 0);
        $this->assertCanEditGame($gameId);
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
        $this->assertCanViewGame($gameId);
        return array_map([$this, 'normalizeSet'], $this->all("SELECT * FROM ce_sets WHERE game_id = ? ORDER BY name ASC", "i", [(int)$gameId]));
    }

    public function getSet($id) {
        $row = $this->one("SELECT * FROM ce_sets WHERE id = ?", "i", [(int)$id]);
        if (!$row) throw new Exception("Set not found");
        $this->assertCanViewGame((int)$row['game_id']);
        return $this->normalizeSet($row);
    }

    public function updateSet($id, $input) {
        $set = $this->getSet($id);
        $this->assertCanEditGame((int)$set['game_id']);
        $this->assertFresh('ce_sets', $id, $input['expectedUpdatedAt'] ?? null);
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
        $this->assertCanEditGame($gameId);
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
        $this->assertCanViewGame($gameId);
        $rows = $this->all("SELECT * FROM ce_templates WHERE game_id = ? ORDER BY name ASC", "i", [(int)$gameId]);
        return array_map([$this, 'normalizeTemplate'], $rows);
    }

    public function getTemplate($id) {
        $row = $this->one("SELECT * FROM ce_templates WHERE id = ?", "i", [(int)$id]);
        if (!$row) throw new Exception("Template not found");
        $this->assertCanViewGame((int)$row['game_id']);
        $template = $this->normalizeTemplate($row);
        $template['fields'] = array_map([$this, 'normalizeField'], $this->all("SELECT * FROM ce_template_fields WHERE template_id = ? ORDER BY sort_order ASC, id ASC", "i", [(int)$id]));
        $template['layout'] = array_map([$this, 'normalizeLayoutElement'], $this->all("SELECT * FROM ce_template_layout_elements WHERE template_id = ? ORDER BY z_index ASC, id ASC", "i", [(int)$id]));
        $template['assets'] = $this->listAssets((int)$template['game_id']);
        $template['enums'] = $this->listGameEnums((int)$template['game_id']);
        return $template;
    }

    public function updateTemplate($id, $input) {
        $template = $this->getTemplate($id);
        $this->assertCanEditGame((int)$template['game_id']);
        $this->assertFresh('ce_templates', $id, $input['expectedUpdatedAt'] ?? null);
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
        $template = $this->getTemplate($templateId);
        $this->assertCanEditGame((int)$template['game_id']);
        if (!is_array($fields)) throw new Exception("Fields must be an array");
        $validTypes = ['text', 'longtext', 'number', 'boolean', 'select', 'multiselect', 'image', 'icon_enum'];
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
        $template = $this->getTemplate($templateId);
        $this->assertCanEditGame((int)$template['game_id']);
        if (!is_array($elements)) throw new Exception("Layout must be an array");
        $now = $this->now();
        mysqli_query($this->conn, "START TRANSACTION");
        try {
            $this->execute("DELETE FROM ce_template_layout_elements WHERE template_id = ?", "i", [(int)$templateId]);
            foreach ($elements as $index => $element) {
                $type = $element['element_type'] ?? $element['elementType'] ?? 'field';
                if (!in_array($type, ['field', 'image'])) throw new Exception("Unsupported layout element type");
                $fieldId = null;
                $assetId = null;
                if ($type === 'field') {
                    $fieldId = isset($element['field_id']) ? (int)$element['field_id'] : (isset($element['fieldId']) ? (int)$element['fieldId'] : null);
                    if (!$fieldId) throw new Exception("Field element missing field id");
                    $field = $this->one("SELECT id FROM ce_template_fields WHERE id = ? AND template_id = ?", "ii", [$fieldId, (int)$templateId]);
                    if (!$field) throw new Exception("Layout field does not belong to template");
                } else {
                    $assetId = isset($element['asset_id']) ? (int)$element['asset_id'] : (isset($element['assetId']) ? (int)$element['assetId'] : null);
                    if (!$assetId) throw new Exception("Image element missing asset id");
                    $asset = $this->one("SELECT id FROM ce_assets WHERE id = ? AND game_id = ?", "ii", [$assetId, (int)$template['game_id']]);
                    if (!$asset) throw new Exception("Image asset does not belong to game");
                }
                $uuid = self::uuidv4();
                $styleJson = $this->styleJsonOrNull($element['style_json'] ?? $element['styleJson'] ?? null);
                $this->execute(
                    "INSERT INTO ce_template_layout_elements (element_uuid, template_id, element_type, field_id, asset_id, x, y, width, height, z_index, rotation, is_visible, style_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    "sisiiddddidisss",
                    [
                        $uuid,
                        (int)$templateId,
                        $type,
                        $fieldId,
                        $assetId,
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
        $this->assertCanEditGame($gameId);
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
        $cardId = (int)mysqli_insert_id($this->conn);
        if (array_key_exists('tagIds', $input)) $this->syncCardTags($cardId, $input['tagIds']);
        return $this->getCard($cardId);
    }

    public function listCards($setId) {
        $this->getSet($setId);
        $cards = array_map([$this, 'normalizeCard'], $this->all("SELECT * FROM ce_cards WHERE set_id = ? ORDER BY name ASC", "i", [(int)$setId]));
        foreach ($cards as &$card) {
            $card['tags'] = $this->listCardTags($card['id']);
        }
        return $cards;
    }

    public function getCard($id) {
        $row = $this->one("SELECT * FROM ce_cards WHERE id = ?", "i", [(int)$id]);
        if (!$row) throw new Exception("Card not found");
        $this->assertCanViewGame((int)$row['game_id']);
        $card = $this->normalizeCard($row);
        $card['template'] = $this->getTemplate($card['template_id']);
        $card['values'] = array_map([$this, 'normalizeValue'], $this->all("SELECT * FROM ce_card_field_values WHERE card_id = ?", "i", [(int)$id]));
        $card['tags'] = $this->listCardTags($card['id']);
        return $card;
    }

    public function updateCard($id, $input) {
        $card = $this->getCard($id);
        $this->assertCanEditGame((int)$card['game_id']);
        $this->assertFresh('ce_cards', $id, $input['expectedUpdatedAt'] ?? null);
        $name = trim($input['name'] ?? $card['name']);
        if ($name === '') throw new Exception("Card name is required");
        $slug = self::slugify($input['slug'] ?? $card['slug']);
        $now = $this->now();
        $this->execute("UPDATE ce_cards SET name = ?, slug = ?, updated_at = ? WHERE id = ?", "sssi", [$name, $slug, $now, (int)$id]);
        if (array_key_exists('tagIds', $input)) $this->syncCardTags($id, $input['tagIds']);
        return $this->getCard($id);
    }

    public function deleteCard($id) {
        $card = $this->getCard($id);
        $this->assertCanEditGame((int)$card['game_id']);
        mysqli_query($this->conn, "START TRANSACTION");
        try {
            $this->execute("DELETE FROM ce_card_field_values WHERE card_id = ?", "i", [(int)$id]);
            $this->execute("DELETE FROM ce_card_tags WHERE card_id = ?", "i", [(int)$id]);
            $this->execute("DELETE FROM ce_cards WHERE id = ?", "i", [(int)$id]);
            mysqli_query($this->conn, "COMMIT");
            return ['deleted' => true, 'id' => (int)$id];
        } catch (Exception $e) {
            mysqli_query($this->conn, "ROLLBACK");
            throw $e;
        }
    }

    public function saveCardFieldValues($cardId, $values) {
        $card = $this->getCard($cardId);
        $this->assertCanEditGame((int)$card['game_id']);
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
                if (in_array($type, ['text', 'longtext', 'select', 'image', 'icon_enum'])) {
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

    public function listCardTags($cardId) {
        $card = $this->one("SELECT game_id FROM ce_cards WHERE id = ?", "i", [(int)$cardId]);
        if (!$card) throw new Exception("Card not found");
        $this->assertCanViewGame((int)$card['game_id']);
        $rows = $this->all(
            "SELECT t.* FROM ce_game_tags t INNER JOIN ce_card_tags ct ON ct.tag_id = t.id WHERE ct.card_id = ? ORDER BY t.name ASC, t.id ASC",
            "i",
            [(int)$cardId]
        );
        return array_map([$this, 'normalizeTag'], $rows);
    }

    private function syncCardTags($cardId, $tagIds) {
        $card = $this->one("SELECT game_id FROM ce_cards WHERE id = ?", "i", [(int)$cardId]);
        if (!$card) throw new Exception("Card not found");
        $gameId = (int)$card['game_id'];
        $this->assertCanEditGame($gameId);
        if (!is_array($tagIds)) $tagIds = [];
        $cleanIds = [];
        foreach ($tagIds as $tagId) {
            $tagId = (int)$tagId;
            if ($tagId > 0 && !in_array($tagId, $cleanIds)) $cleanIds[] = $tagId;
        }
        $now = $this->now();
        $this->execute("DELETE FROM ce_card_tags WHERE card_id = ?", "i", [(int)$cardId]);
        foreach ($cleanIds as $tagId) {
            $tag = $this->one("SELECT id FROM ce_game_tags WHERE id = ? AND game_id = ?", "ii", [$tagId, $gameId]);
            if (!$tag) continue;
            $this->execute(
                "INSERT INTO ce_card_tags (card_id, tag_id, created_at) VALUES (?, ?, ?)",
                "iis",
                [(int)$cardId, $tagId, $now]
            );
        }
    }

    public function listAssets($gameId) {
        $this->assertCanViewGame($gameId);
        return array_map([$this, 'normalizeAsset'], $this->all("SELECT * FROM ce_assets WHERE game_id = ? ORDER BY created_at DESC, id DESC", "i", [(int)$gameId]));
    }

    public function createAsset($input) {
        $gameId = (int)$input['gameId'];
        $this->assertCanEditGame($gameId);
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
        unset($game['tags']);
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
