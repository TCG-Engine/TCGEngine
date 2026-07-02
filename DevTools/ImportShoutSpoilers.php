<?php

require_once(__DIR__ . '/../Database/ConnectionManager.php');
require_once(__DIR__ . '/../CardEditor/Database/CardAuthoringDB.php');

$options = parseOptions($argv);
$sourceUrl = $options['url'] ?? 'https://shoutatyourdecks.com/spoilers';
$dryRun = isset($options['dry-run']);
$limit = isset($options['limit']) ? max(0, (int)$options['limit']) : 0;

$conn = GetLocalMySQLConnection();
if (!$conn) {
    fwrite(STDERR, "Could not connect to local MySQL.\n");
    exit(1);
}

try {
    new CardAuthoringDB($conn);

    $game = fetchOne($conn, "SELECT * FROM ce_games WHERE slug = 'grand-archive-sim'");
    $set = fetchOne($conn, "SELECT * FROM ce_sets WHERE slug = 'grand-archive-spoilers'");
    $template = fetchOne($conn, "SELECT * FROM ce_templates WHERE slug = 'grand-archive-card'");
    if (!$game || !$set || !$template) {
        throw new Exception("Missing GrandArchiveSim CardEditor seed. Run: php DevTools/CreateGrandArchiveCardEditorSeed.php");
    }

    $fields = templateFieldsByKey($conn, (int)$template['id']);
    foreach (['uuid', 'name', 'set', 'image_url'] as $requiredField) {
        if (!isset($fields[$requiredField])) {
            throw new Exception("Grand Archive template is missing required field: $requiredField");
        }
    }

    $html = fetchUrl($sourceUrl);
    $candidates = parseShoutSpoilers($html, $sourceUrl);
    if ($limit > 0) $candidates = array_slice($candidates, 0, $limit);

    $created = 0;
    $updated = 0;
    $now = date('Y-m-d H:i:s');
    foreach ($candidates as $index => $candidate) {
        $candidate['card_id'] = grandArchiveShoutTempId($candidate['image_path']);
        $candidate['name'] = nameFromImagePath($candidate['image_path']);
        $candidate['slug'] = CardAuthoringDB::slugify($candidate['card_id']);

        $existing = fetchOne(
            $conn,
            "SELECT c.* FROM ce_cards c INNER JOIN ce_card_field_values v ON v.card_id = c.id WHERE c.set_id = ? AND v.field_id = ? AND v.value_text = ? LIMIT 1",
            'iis',
            [(int)$set['id'], (int)$fields['uuid']['id'], $candidate['card_id']]
        );
        if (!$existing) {
            $existing = fetchOne($conn, "SELECT * FROM ce_cards WHERE set_id = ? AND slug = ? LIMIT 1", 'is', [(int)$set['id'], $candidate['slug']]);
        }

        if ($dryRun) {
            echo sprintf(
                "%s %-8s %-48s %s\n",
                $existing ? "UPDATE" : "CREATE",
                $candidate['card_id'],
                truncate($candidate['name'], 48),
                $candidate['image_url']
            );
            continue;
        }

        if ($existing) {
            $cardId = (int)$existing['id'];
            execStmt(
                $conn,
                "UPDATE ce_cards SET name = ?, slug = ?, template_id = ?, updated_at = ? WHERE id = ?",
                'ssisi',
                [$candidate['name'], $candidate['slug'], (int)$template['id'], $now, $cardId]
            );
            $updated++;
        } else {
            execStmt(
                $conn,
                "INSERT INTO ce_cards (card_uuid, game_id, set_id, template_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                'siiissss',
                [CardAuthoringDB::uuidv4(), (int)$game['id'], (int)$set['id'], (int)$template['id'], $candidate['name'], $candidate['slug'], $now, $now]
            );
            $cardId = (int)mysqli_insert_id($conn);
            $created++;
        }

        upsertTextValue($conn, $cardId, (int)$fields['uuid']['id'], $candidate['card_id'], $now);
        upsertTextValue($conn, $cardId, (int)$fields['name']['id'], $candidate['name'], $now);
        upsertTextValue($conn, $cardId, (int)$fields['set']['id'], $candidate['section'], $now);
        upsertTextValue($conn, $cardId, (int)$fields['image_url']['id'], $candidate['image_url'], $now);
    }

    if ($dryRun) {
        echo "Dry run: " . count($candidates) . " spoiler candidates found.\n";
    } else {
        echo "Imported Shout spoilers into CardEditor.\n";
        echo "Created: $created\n";
        echo "Updated: $updated\n";
        echo "Total candidates: " . count($candidates) . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    mysqli_close($conn);
    exit(1);
}

mysqli_close($conn);

function parseOptions($argv) {
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (substr($arg, 0, 2) !== '--') continue;
        $arg = substr($arg, 2);
        $parts = explode('=', $arg, 2);
        $options[$parts[0]] = count($parts) > 1 ? $parts[1] : true;
    }
    return $options;
}

function fetchUrl($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header' => "User-Agent: TCGEngine-CardEditorSpoilerImporter/1.0\r\n"
        ]
    ]);
    $html = @file_get_contents($url, false, $context);
    if ($html === false || trim($html) === '') {
        throw new Exception("Could not fetch spoiler source: $url");
    }
    return $html;
}

function parseShoutSpoilers($html, $sourceUrl) {
    $base = parse_url($sourceUrl, PHP_URL_SCHEME) . '://' . parse_url($sourceUrl, PHP_URL_HOST);
    $pattern = '/<h4\b[^>]*>.*?<\/h4>|<img\b[^>]*>/is';
    preg_match_all($pattern, $html, $matches);

    $section = 'Shout Spoilers';
    $cards = [];
    $seen = [];
    foreach ($matches[0] as $token) {
        if (stripos($token, '<h4') === 0) {
            $section = normalizeWhitespace(html_entity_decode(strip_tags($token), ENT_QUOTES));
            if ($section === '') $section = 'Shout Spoilers';
            continue;
        }
        if (stripos($token, 'card-fade') === false) continue;
        if (!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $token, $srcMatch)) continue;
        $path = html_entity_decode($srcMatch[1], ENT_QUOTES);
        if (stripos($path, 'card-back') !== false) continue;
        if (isset($seen[$path])) continue;
        $seen[$path] = true;
        $cards[] = [
            'section' => $section,
            'image_path' => $path,
            'image_url' => absoluteUrl($base, $path)
        ];
    }
    return $cards;
}

function absoluteUrl($base, $path) {
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    if ($path === '') return $base;
    if ($path[0] !== '/') $path = '/' . $path;
    return rtrim($base, '/') . $path;
}

function grandArchiveShoutTempId($imagePath) {
    $base = pathinfo(parse_url($imagePath, PHP_URL_PATH), PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9]+/', '-', $base);
    $base = trim($base, '-');
    return 'GA-SHOUT-' . strtoupper($base);
}

function nameFromImagePath($imagePath) {
    $base = pathinfo(parse_url($imagePath, PHP_URL_PATH), PATHINFO_FILENAME);
    $base = preg_replace('/-(prd|prdsd|prd1e)(-(csr|cur))?$/i', '', $base);
    $base = preg_replace('/[^a-zA-Z0-9]+/', ' ', $base);
    $base = normalizeWhitespace($base);
    if ($base === '') return 'Shout Spoiler Card';
    $lowerWords = ['a' => true, 'an' => true, 'and' => true, 'at' => true, 'by' => true, 'for' => true, 'in' => true, 'of' => true, 'on' => true, 'or' => true, 'the' => true, 'to' => true, 'with' => true];
    $acronyms = ['qa' => true, 'x' => true];
    $parts = explode(' ', strtolower($base));
    return implode(' ', array_map(function($part, $index) use ($lowerWords, $acronyms) {
        if ($index > 0 && isset($lowerWords[$part])) return $part;
        if (isset($acronyms[$part])) return strtoupper($part);
        return ucfirst($part);
    }, $parts, array_keys($parts)));
}

function normalizeWhitespace($value) {
    return trim(preg_replace('/\s+/', ' ', (string)$value));
}

function truncate($value, $length) {
    return strlen($value) <= $length ? $value : substr($value, 0, $length - 3) . '...';
}

function templateFieldsByKey($conn, $templateId) {
    $rows = fetchAll($conn, "SELECT * FROM ce_template_fields WHERE template_id = ?", 'i', [$templateId]);
    $fields = [];
    foreach ($rows as $row) $fields[$row['field_key']] = $row;
    return $fields;
}

function fetchAll($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception(mysqli_error($conn));
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($error);
    }
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    return $rows;
}

function fetchOne($conn, $sql, $types = '', $params = []) {
    $rows = fetchAll($conn, $sql, $types, $params);
    return count($rows) > 0 ? $rows[0] : null;
}

function execStmt($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception(mysqli_error($conn));
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($error);
    }
    mysqli_stmt_close($stmt);
}

function upsertTextValue($conn, $cardId, $fieldId, $value, $now) {
    execStmt(
        $conn,
        "INSERT INTO ce_card_field_values (card_id, field_id, value_text, value_number, value_boolean, value_json, updated_at) VALUES (?, ?, ?, NULL, NULL, NULL, ?) ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), value_number = NULL, value_boolean = NULL, value_json = NULL, updated_at = VALUES(updated_at)",
        'iiss',
        [$cardId, $fieldId, $value, $now]
    );
}
