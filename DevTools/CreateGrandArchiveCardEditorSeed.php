<?php

require_once(__DIR__ . '/../Database/ConnectionManager.php');
require_once(__DIR__ . '/../CardEditor/Database/CardAuthoringDB.php');

$conn = GetLocalMySQLConnection();
if (!$conn) {
    fwrite(STDERR, "Could not connect to local MySQL.\n");
    exit(1);
}

try {
    new CardAuthoringDB($conn);

    $now = date('Y-m-d H:i:s');

    $game = upsertGame($conn, [
        'name' => 'Grand Archive Sim',
        'slug' => 'grand-archive-sim',
        'description' => 'CardEditor staging game for GrandArchiveSim cards, including spoiler imports.'
    ], $now);

    $set = upsertSet($conn, $game['id'], [
        'name' => 'Grand Archive Spoilers',
        'slug' => 'grand-archive-spoilers',
        'description' => 'Temporary-but-real Grand Archive spoiler cards before official source migration.'
    ], $now);

    $template = upsertTemplate($conn, $game['id'], [
        'name' => 'Grand Archive Card',
        'slug' => 'grand-archive-card',
        'description' => 'GrandArchiveSim import-compatible card fields for official and spoiler sources.',
        'canvas_width' => 750,
        'canvas_height' => 1050,
        'canvas_background_color' => '#f8f6ef',
        'safe_area_padding' => 36
    ], $now);

    $fields = [
        ['uuid', 'Card ID', 'text', 'Engine CardID or temporary spoiler ID.'],
        ['name', 'Name', 'text', 'Printed card name.'],
        ['element', 'Element', 'text', 'Printed element or elements.'],
        ['type', 'Type', 'text', 'Primary card type.'],
        ['speed', 'Speed', 'text', 'Timing/speed value when applicable.'],
        ['cost_memory', 'Memory Cost', 'number', 'Memory cost.'],
        ['cost_reserve', 'Reserve Cost', 'number', 'Reserve/materialization cost.'],
        ['level', 'Level', 'number', 'Level requirement or champion level.'],
        ['power', 'Power', 'number', 'Power value.'],
        ['life', 'Life', 'number', 'Life or HP value.'],
        ['durability', 'Durability', 'number', 'Durability value.'],
        ['classes', 'Classes', 'text', 'Comma-separated class list.'],
        ['subtypes', 'Subtypes', 'text', 'Comma-separated subtype list.'],
        ['effect', 'Effect', 'longtext', 'Rules text.'],
        ['set', 'Set', 'text', 'Set code or source set name.'],
        ['image_url', 'Image URL', 'image', 'Source image URL or imported asset reference.']
    ];

    $fieldIds = [];
    foreach ($fields as $index => $field) {
        $fieldIds[$field[0]] = upsertField($conn, $template['id'], [
            'field_key' => $field[0],
            'label' => $field[1],
            'field_type' => $field[2],
            'help_text' => $field[3],
            'sort_order' => $index
        ], $now);
    }

    ensureDefaultLayout($conn, $template['id'], $fieldIds, $now);

    echo "GrandArchiveSim CardEditor seed ready.\n";
    echo "Game: {$game['name']} (#{$game['id']})\n";
    echo "Set: {$set['name']} (#{$set['id']})\n";
    echo "Template: {$template['name']} (#{$template['id']})\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    mysqli_close($conn);
    exit(1);
}

mysqli_close($conn);

function uuidv4() {
    return CardAuthoringDB::uuidv4();
}

function fetchOne($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception(mysqli_error($conn));
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($error);
    }
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function execStmt($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception(mysqli_error($conn));
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($error);
    }
    mysqli_stmt_close($stmt);
}

function upsertGame($conn, $input, $now) {
    $existing = fetchOne($conn, "SELECT * FROM ce_games WHERE slug = ?", 's', [$input['slug']]);
    if ($existing) {
        execStmt(
            $conn,
            "UPDATE ce_games SET name = ?, description = ?, updated_at = ? WHERE id = ?",
            'sssi',
            [$input['name'], $input['description'], $now, (int)$existing['id']]
        );
        return fetchOne($conn, "SELECT * FROM ce_games WHERE id = ?", 'i', [(int)$existing['id']]);
    }
    execStmt(
        $conn,
        "INSERT INTO ce_games (game_uuid, name, slug, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
        'ssssss',
        [uuidv4(), $input['name'], $input['slug'], $input['description'], $now, $now]
    );
    return fetchOne($conn, "SELECT * FROM ce_games WHERE id = ?", 'i', [mysqli_insert_id($conn)]);
}

function upsertSet($conn, $gameId, $input, $now) {
    $existing = fetchOne($conn, "SELECT * FROM ce_sets WHERE game_id = ? AND slug = ?", 'is', [$gameId, $input['slug']]);
    if ($existing) {
        execStmt(
            $conn,
            "UPDATE ce_sets SET name = ?, description = ?, updated_at = ? WHERE id = ?",
            'sssi',
            [$input['name'], $input['description'], $now, (int)$existing['id']]
        );
        return fetchOne($conn, "SELECT * FROM ce_sets WHERE id = ?", 'i', [(int)$existing['id']]);
    }
    execStmt(
        $conn,
        "INSERT INTO ce_sets (set_uuid, game_id, name, slug, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
        'sisssss',
        [uuidv4(), $gameId, $input['name'], $input['slug'], $input['description'], $now, $now]
    );
    return fetchOne($conn, "SELECT * FROM ce_sets WHERE id = ?", 'i', [mysqli_insert_id($conn)]);
}

function upsertTemplate($conn, $gameId, $input, $now) {
    $existing = fetchOne($conn, "SELECT * FROM ce_templates WHERE game_id = ? AND slug = ?", 'is', [$gameId, $input['slug']]);
    if ($existing) {
        execStmt(
            $conn,
            "UPDATE ce_templates SET name = ?, description = ?, canvas_width = ?, canvas_height = ?, canvas_background_color = ?, safe_area_padding = ?, updated_at = ? WHERE id = ?",
            'ssiisisi',
            [$input['name'], $input['description'], $input['canvas_width'], $input['canvas_height'], $input['canvas_background_color'], $input['safe_area_padding'], $now, (int)$existing['id']]
        );
        return fetchOne($conn, "SELECT * FROM ce_templates WHERE id = ?", 'i', [(int)$existing['id']]);
    }
    execStmt(
        $conn,
        "INSERT INTO ce_templates (template_uuid, game_id, name, slug, description, canvas_width, canvas_height, canvas_background_color, canvas_background_asset_id, safe_area_padding, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)",
        'sisssiisiss',
        [uuidv4(), $gameId, $input['name'], $input['slug'], $input['description'], $input['canvas_width'], $input['canvas_height'], $input['canvas_background_color'], $input['safe_area_padding'], $now, $now]
    );
    return fetchOne($conn, "SELECT * FROM ce_templates WHERE id = ?", 'i', [mysqli_insert_id($conn)]);
}

function upsertField($conn, $templateId, $input, $now) {
    $existing = fetchOne($conn, "SELECT * FROM ce_template_fields WHERE template_id = ? AND field_key = ?", 'is', [$templateId, $input['field_key']]);
    if ($existing) {
        execStmt(
            $conn,
            "UPDATE ce_template_fields SET label = ?, field_type = ?, help_text = ?, sort_order = ?, updated_at = ? WHERE id = ?",
            'sssisi',
            [$input['label'], $input['field_type'], $input['help_text'], $input['sort_order'], $now, (int)$existing['id']]
        );
        return (int)$existing['id'];
    }
    execStmt(
        $conn,
        "INSERT INTO ce_template_fields (field_uuid, template_id, field_key, label, field_type, help_text, default_value, sort_order, settings_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, NULL, ?, ?)",
        'sissssiss',
        [uuidv4(), $templateId, $input['field_key'], $input['label'], $input['field_type'], $input['help_text'], $input['sort_order'], $now, $now]
    );
    return (int)mysqli_insert_id($conn);
}

function ensureDefaultLayout($conn, $templateId, $fieldIds, $now) {
    $existing = fetchOne($conn, "SELECT id FROM ce_template_layout_elements WHERE template_id = ? LIMIT 1", 'i', [$templateId]);
    if ($existing) return;

    $layout = [
        ['image_url', 36, 36, 300, 420, 0, ['objectFit' => 'cover']],
        ['name', 360, 36, 354, 50, 1, ['fontSize' => 30, 'fontWeight' => '700']],
        ['type', 360, 96, 170, 34, 2, ['fontSize' => 18]],
        ['element', 544, 96, 170, 34, 3, ['fontSize' => 18]],
        ['cost_memory', 360, 146, 80, 44, 4, ['fontSize' => 24, 'textAlign' => 'center']],
        ['cost_reserve', 454, 146, 80, 44, 5, ['fontSize' => 24, 'textAlign' => 'center']],
        ['speed', 548, 146, 166, 44, 6, ['fontSize' => 18]],
        ['classes', 360, 210, 354, 34, 7, ['fontSize' => 16]],
        ['subtypes', 360, 252, 354, 34, 8, ['fontSize' => 16]],
        ['effect', 36, 486, 678, 360, 9, ['fontSize' => 20, 'lineHeight' => 1.22]],
        ['level', 36, 880, 90, 44, 10, ['fontSize' => 22, 'textAlign' => 'center']],
        ['power', 142, 880, 90, 44, 11, ['fontSize' => 22, 'textAlign' => 'center']],
        ['life', 248, 880, 90, 44, 12, ['fontSize' => 22, 'textAlign' => 'center']],
        ['durability', 354, 880, 120, 44, 13, ['fontSize' => 22, 'textAlign' => 'center']],
        ['set', 492, 880, 102, 34, 14, ['fontSize' => 14]],
        ['uuid', 492, 918, 222, 34, 15, ['fontSize' => 13]]
    ];

    foreach ($layout as $item) {
        [$key, $x, $y, $width, $height, $z, $style] = $item;
        if (!isset($fieldIds[$key])) continue;
        execStmt(
            $conn,
            "INSERT INTO ce_template_layout_elements (element_uuid, template_id, element_type, field_id, asset_id, x, y, width, height, z_index, rotation, is_visible, style_json, created_at, updated_at) VALUES (?, ?, 'field', ?, NULL, ?, ?, ?, ?, ?, 0, 1, ?, ?, ?)",
            'siiddddisss',
            [uuidv4(), $templateId, $fieldIds[$key], $x, $y, $width, $height, $z, json_encode($style), $now, $now]
        );
    }
}

