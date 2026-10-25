<?php

// Only authoring operations belong here. Ability PHP, codegen, and simulator tools remain in McpServer/.
class CardEditingTools {
    public static function definitions(): array {
        $id = ['type' => 'integer', 'minimum' => 1];
        $text = ['type' => 'string', 'maxLength' => 10000];
        $name = ['type' => 'string', 'minLength' => 1, 'maxLength' => 255];
        $nullableText = ['type' => ['string', 'null'], 'maxLength' => 10000];
        $items = static function($schema) { return ['type' => 'array', 'items' => $schema, 'maxItems' => 1000]; };
        $object = static function($properties, $required = []) {
            return ['type' => 'object', 'properties' => (object)$properties, 'required' => $required, 'additionalProperties' => false];
        };
        $paging = ['query' => $text, 'offset' => ['type' => 'integer', 'minimum' => 0], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]];
        $common = ['name' => $name, 'slug' => $name, 'description' => $nullableText];
        $fresh = ['expectedUpdatedAt' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 32, 'description' => 'updated_at from the most recent read. Reload and reconcile on conflict.']];
        $canvas = ['canvasWidth' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10000], 'canvasHeight' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10000],
            'canvasBackgroundColor' => $name, 'canvasBackgroundAssetId' => ['type' => ['integer', 'null'], 'minimum' => 1], 'safeAreaPadding' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10000]];
        $field = $object(['id' => $id, 'label' => $name, 'fieldKey' => $name, 'fieldType' => ['type' => 'string', 'enum' => ['text', 'longtext', 'number', 'boolean', 'select', 'multiselect', 'image', 'icon_enum']],
            'helpText' => $nullableText, 'defaultValue' => $nullableText, 'sortOrder' => ['type' => 'integer'], 'settingsJson' => ['type' => ['object', 'null']]], ['label', 'fieldType']);
        $number = ['type' => 'number'];
        $layout = $object(['element_type' => ['type' => 'string', 'enum' => ['field', 'image']], 'field_id' => $id, 'asset_id' => $id,
            'x' => $number, 'y' => $number, 'width' => ['type' => 'number', 'minimum' => 1], 'height' => ['type' => 'number', 'minimum' => 1], 'rotation' => $number,
            'z_index' => ['type' => 'integer'], 'is_visible' => ['type' => 'boolean'], 'styleJson' => ['type' => 'object']], ['element_type']);
        $value = $object(['fieldId' => $id, 'value' => ['type' => ['string', 'number', 'boolean', 'array', 'null'], 'items' => $text]], ['fieldId', 'value']);
        $definitions = [];
        $add = static function($tool, $description, $method, $mode, $properties, $required = [], $idKey = null, $destructive = false) use (&$definitions, $object) {
            $read = in_array($mode, ['read', 'list'], true);
            $definitions[$tool] = ['name' => $tool, 'description' => $description, 'inputSchema' => $object($properties, $required),
                'annotations' => ['readOnlyHint' => $read, 'destructiveHint' => $destructive, 'idempotentHint' => $read, 'openWorldHint' => false],
                '_method' => $method, '_mode' => $mode, '_id' => $idKey];
        };
        $add('list_games', 'Find accessible card editor games. Lists are paginated; query filters names.', 'listGames', 'list', $paging);
        $add('get_game', 'Read a game with its tags and icon enumerations.', 'getGame', 'read', ['id' => $id], ['id'], 'id');
        $add('create_game', 'Create a private card editor game owned by your account.', 'createGame', 'create', $common, ['name']);
        $add('update_game', 'Update game metadata. Does not change ownership or sharing.', 'updateGame', 'update', ['id' => $id] + $common + $fresh, ['id', 'expectedUpdatedAt'], 'id');
        foreach (['set' => ['listSets', 'getSet', 'createSet', 'updateSet'], 'template' => ['listTemplates', 'getTemplate', 'createTemplate', 'updateTemplate']] as $entity => $methods) {
            $add('list_' . $entity . 's', 'Find ' . $entity . 's in a game.', $methods[0], 'list', ['gameId' => $id] + $paging, ['gameId'], 'gameId');
            $add('get_' . $entity, 'Read a ' . $entity . ($entity === 'template' ? ' including fields and layout. Read before editing.' : '.'), $methods[1], 'read', ['id' => $id], ['id'], 'id');
            $extra = $entity === 'template' ? $canvas : [];
            $add('create_' . $entity, 'Create a ' . $entity . ' in a game.', $methods[2], 'create', ['gameId' => $id] + $common + $extra, ['gameId', 'name']);
            $add('update_' . $entity, 'Update ' . $entity . ' metadata.', $methods[3], 'update', ['id' => $id] + $common + $extra + $fresh, ['id', 'expectedUpdatedAt'], 'id');
        }
        $add('duplicate_template', 'Copy a template including fields and layout.', 'duplicateTemplate', 'update', ['id' => $id, 'name' => $name], ['id'], 'id');
        $add('save_template_fields', 'Replace ALL fields. Preserve existing field IDs. Omitted fields and their card values/layout are deleted. Read get_template first.', 'saveTemplateFields', 'array', ['templateId' => $id, 'fields' => $items($field)] + $fresh, ['templateId', 'fields', 'expectedUpdatedAt'], 'templateId', true);
        $add('save_template_layout', 'Replace ALL layout elements. Each field element needs field_id; each image needs asset_id. Read get_template first.', 'saveTemplateLayout', 'array', ['templateId' => $id, 'layout' => $items($layout)] + $fresh, ['templateId', 'layout', 'expectedUpdatedAt'], 'templateId', true);
        $add('list_cards', 'Find cards in a set; query filters names.', 'listCards', 'list', ['setId' => $id] + $paging, ['setId'], 'setId');
        $add('get_card', 'Read a card, typed field values, tags, and full template for editing or previewing in the editor.', 'getCard', 'read', ['id' => $id], ['id'], 'id');
        $add('create_card', 'Create a card using a set and template from the same game. Use save_card_fields for rules text, stats, and art.', 'createCard', 'create', ['gameId' => $id, 'setId' => $id, 'templateId' => $id, 'name' => $name, 'slug' => $name, 'tagIds' => $items($id)], ['gameId', 'setId', 'templateId', 'name']);
        $add('update_card', 'Update name, slug, or tags. Use save_card_fields for rules text, stats, and artwork.', 'updateCard', 'update', ['id' => $id, 'name' => $name, 'slug' => $name, 'tagIds' => $items($id)] + $fresh, ['id', 'expectedUpdatedAt'], 'id');
        $add('save_card_fields', 'Set selected fields; omitted fields stay unchanged. Read get_card for field IDs/types. Image values use asset relative_path; icon_enum uses option IDs.', 'saveCardFieldValues', 'array', ['cardId' => $id, 'values' => $items($value)] + $fresh, ['cardId', 'values', 'expectedUpdatedAt'], 'cardId');
        $add('list_assets', 'Find artwork and images belonging to a game, including usage.', 'listAssets', 'list', ['gameId' => $id] + $paging, ['gameId'], 'gameId');
        $add('upload_asset', 'Upload base64-encoded PNG/JPEG/WebP/GIF bytes (maximum 5 MiB decoded) to a game. No remote URL fetching. Returns relative_path for image fields.', null, 'upload', ['gameId' => $id, 'filename' => $name, 'base64' => ['type' => 'string', 'maxLength' => 6990508]], ['gameId', 'filename', 'base64']);
        foreach (['card' => 'deleteCard', 'template' => 'deleteTemplate', 'asset' => 'deleteAsset'] as $entity => $method) {
            $add('delete_' . $entity, 'Permanently delete this ' . $entity . '. Request only when the user asks for deletion.', $method, 'delete', ['id' => $id], ['id'], 'id', true);
        }
        $add('export_set', 'Export a set as structured card editor data.', 'exportSet', 'read', ['id' => $id], ['id'], 'id');
        return $definitions;
    }

    public static function advertised(string $scope): array {
        $result = [];
        foreach (self::definitions() as $definition) {
            if ($scope === 'read' && !$definition['annotations']['readOnlyHint']) continue;
            unset($definition['_method'], $definition['_mode'], $definition['_id']);
            $result[] = $definition;
        }
        return $result;
    }

    public static function call($db, string $scope, string $name, $arguments) {
        $definition = self::definitions()[$name] ?? null;
        if (!$definition) throw new InvalidArgumentException('Unknown card editing tool');
        if ($scope !== 'write' && !$definition['annotations']['readOnlyHint']) throw new RuntimeException('This token has read-only permission');
        self::validate($arguments, $definition['inputSchema'], 'arguments');
        $args = (array)$arguments;
        $method = $definition['_method'];
        $key = $definition['_id'];
        switch ($definition['_mode']) {
            case 'list':
                $rows = $key ? $db->$method($args[$key]) : $db->$method();
                $query = trim($args['query'] ?? '');
                if ($query !== '') $rows = array_values(array_filter($rows, static function($row) use ($query) {
                    return stripos((string)($row['name'] ?? $row['original_filename'] ?? ''), $query) !== false;
                }));
                $offset = $args['offset'] ?? 0;
                $limit = $args['limit'] ?? 50;
                return ['items' => array_slice($rows, $offset, $limit), 'total' => count($rows), 'nextOffset' => $offset + $limit < count($rows) ? $offset + $limit : null];
            case 'read': case 'delete': return $db->$method($args[$key]);
            case 'create': return $db->$method(self::arrays($args));
            case 'update': return $db->$method($args[$key], self::arrays($args));
            case 'array':
                $payloadKey = ['save_template_fields' => 'fields', 'save_template_layout' => 'layout', 'save_card_fields' => 'values'][$name];
                return $db->$method($args[$key], self::arrays($args[$payloadKey]), $args['expectedUpdatedAt']);
            case 'upload': return self::upload($db, $args);
        }
        throw new LogicException('Unsupported tool');
    }

    private static function arrays($value) {
        if (is_object($value)) $value = (array)$value;
        if (is_array($value)) return array_map([self::class, 'arrays'], $value);
        return $value;
    }

    // Validate the subset of JSON Schema used by this registry before passing inputs to PHP/SQL.
    public static function validate($value, array $schema, string $path): void {
        $types = (array)($schema['type'] ?? []);
        $valid = !$types;
        foreach ($types as $type) {
            $valid = $valid || ($type === 'object' && is_object($value)) || ($type === 'array' && is_array($value))
                || ($type === 'integer' && is_int($value)) || ($type === 'number' && (is_int($value) || is_float($value)))
                || ($type === 'string' && is_string($value)) || ($type === 'boolean' && is_bool($value)) || ($type === 'null' && $value === null);
        }
        if (!$valid) throw new InvalidArgumentException($path . ' has an invalid type');
        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) throw new InvalidArgumentException($path . ' has an invalid value');
        if (is_string($value) && (strlen($value) < ($schema['minLength'] ?? 0) || strlen($value) > ($schema['maxLength'] ?? PHP_INT_MAX))) throw new InvalidArgumentException($path . ' has an invalid length');
        if (is_int($value) || is_float($value)) {
            if ($value < ($schema['minimum'] ?? -INF) || $value > ($schema['maximum'] ?? INF)) throw new InvalidArgumentException($path . ' is out of range');
        }
        if (is_array($value)) {
            if (count($value) > ($schema['maxItems'] ?? 1000)) throw new InvalidArgumentException($path . ' has too many items');
            if (isset($schema['items'])) foreach ($value as $i => $item) self::validate($item, $schema['items'], $path . '[' . $i . ']');
        }
        if (is_object($value)) {
            foreach ($schema['required'] ?? [] as $key) if (!property_exists($value, $key)) throw new InvalidArgumentException($path . '.' . $key . ' is required');
            $properties = (array)($schema['properties'] ?? []);
            foreach (get_object_vars($value) as $key => $item) {
                if (isset($properties[$key])) self::validate($item, $properties[$key], $path . '.' . $key);
                elseif (($schema['additionalProperties'] ?? true) === false) throw new InvalidArgumentException($path . '.' . $key . ' is not supported');
            }
        }
    }

    private static function upload($db, array $args): array {
        $game = $db->requireGameEditAccess($args['gameId']);
        $bytes = base64_decode($args['base64'], true);
        if ($bytes === false || strlen($bytes) > 5 * 1024 * 1024) throw new InvalidArgumentException('Invalid base64 or image exceeds 5 MiB');
        $info = @getimagesizefromstring($bytes);
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!$info || !isset($allowed[$info['mime']])) throw new InvalidArgumentException('Unsupported image type');
        if ($info[0] > 10000 || $info[1] > 10000) throw new InvalidArgumentException('Image dimensions exceed 10000 pixels');
        if (!preg_match('/^[a-f0-9-]{36}$/D', $game['game_uuid'])) throw new RuntimeException('Invalid game UUID');
        $uuid = CardAuthoringDB::uuidv4();
        $extension = $allowed[$info['mime']];
        $relativeDir = 'Assets/' . $game['game_uuid'] . '/images';
        $directory = dirname(__DIR__) . '/' . $relativeDir;
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) throw new RuntimeException('Could not create image directory');
        $relative = $relativeDir . '/' . $uuid . '.' . $extension;
        $absolute = dirname(__DIR__) . '/' . $relative;
        if (file_put_contents($absolute, $bytes, LOCK_EX) !== strlen($bytes)) {
            if (is_file($absolute)) unlink($absolute);
            throw new RuntimeException('Could not store image');
        }
        try {
            return $db->createAsset(['gameId' => $args['gameId'], 'assetUuid' => $uuid, 'assetKind' => 'image',
                'originalFilename' => basename($args['filename']), 'mimeType' => $info['mime'], 'extension' => $extension,
                'relativePath' => $relative, 'width' => $info[0], 'height' => $info[1], 'fileSize' => strlen($bytes)]);
        } catch (Throwable $error) {
            unlink($absolute);
            throw $error;
        }
    }
}
