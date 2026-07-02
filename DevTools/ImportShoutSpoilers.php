<?php

require_once(__DIR__ . '/../Database/ConnectionManager.php');
require_once(__DIR__ . '/../CardEditor/Database/CardAuthoringDB.php');

$options = parseOptions($argv);
$sourceUrl = $options['url'] ?? 'https://shoutatyourdecks.com/spoilers';
$dryRun = isset($options['dry-run']);
$limit = isset($options['limit']) ? max(0, (int)$options['limit']) : 0;
$transcribe = isset($options['transcribe']);
$transcribeLimit = isset($options['transcribe-limit']) ? max(0, (int)$options['transcribe-limit']) : 0;
$overwriteTranscription = isset($options['overwrite-transcription']);
$codexModel = $options['codex-model'] ?? 'gpt-5.4-mini';

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
    foreach (['uuid', 'name', 'element', 'type', 'speed', 'cost_memory', 'cost_reserve', 'level', 'power', 'life', 'durability', 'classes', 'subtypes', 'effect', 'set', 'image_url'] as $requiredField) {
        if (!isset($fields[$requiredField])) {
            throw new Exception("Grand Archive template is missing required field: $requiredField");
        }
    }

    $html = fetchUrl($sourceUrl);
    $candidates = parseShoutSpoilers($html, $sourceUrl);
    if ($limit > 0) $candidates = array_slice($candidates, 0, $limit);

    $created = 0;
    $updated = 0;
    $transcribed = 0;
    $transcriptionSkipped = 0;
    $transcriptionFailed = 0;
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

        if ($transcribe) {
            if ($transcribeLimit > 0 && $transcribed >= $transcribeLimit) {
                $transcriptionSkipped++;
                continue;
            }
            $currentValues = cardValuesByFieldKey($conn, $cardId, $fields);
            if (!$overwriteTranscription && !cardNeedsTranscription($currentValues)) {
                $transcriptionSkipped++;
                continue;
            }
            try {
                echo "Transcribing {$candidate['card_id']} {$candidate['name']}...\n";
                $result = transcribeWithCodexCli($candidate['image_url'], $candidate['card_id'], $codexModel);
                applyTranscription($conn, $cardId, $fields, $result, $now, $overwriteTranscription);
                $transcribed++;
            } catch (Throwable $transcriptionError) {
                $transcriptionFailed++;
                fwrite(STDERR, "Transcription failed for {$candidate['card_id']}: " . $transcriptionError->getMessage() . "\n");
            }
        }
    }

    if ($dryRun) {
        echo "Dry run: " . count($candidates) . " spoiler candidates found.\n";
    } else {
        echo "Imported Shout spoilers into CardEditor.\n";
        echo "Created: $created\n";
        echo "Updated: $updated\n";
        echo "Total candidates: " . count($candidates) . "\n";
        if ($transcribe) {
            echo "Transcribed: $transcribed\n";
            echo "Transcription skipped: $transcriptionSkipped\n";
            echo "Transcription failed: $transcriptionFailed\n";
        }
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

function fetchBinaryUrl($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: TCGEngine-CardEditorSpoilerImporter/1.0\r\n"
        ]
    ]);
    $bytes = @file_get_contents($url, false, $context);
    if ($bytes === false || $bytes === '') {
        throw new Exception("Could not fetch image: $url");
    }
    return $bytes;
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

function transcriptionFieldKeys() {
    return ['name', 'element', 'type', 'speed', 'cost_memory', 'cost_reserve', 'level', 'power', 'life', 'durability', 'classes', 'subtypes', 'effect'];
}

function cardNeedsTranscription($currentValues) {
    foreach (['element', 'type', 'speed', 'cost_memory', 'cost_reserve', 'level', 'power', 'life', 'durability', 'classes', 'subtypes', 'effect'] as $fieldKey) {
        if (!emptyValue($currentValues[$fieldKey] ?? null)) return false;
    }
    return true;
}

function emptyValue($value) {
    return $value === null || $value === '' || (is_array($value) && count($value) === 0);
}

function cardValuesByFieldKey($conn, $cardId, $fields) {
    $rows = fetchAll($conn, "SELECT field_id, value_text, value_number, value_boolean, value_json FROM ce_card_field_values WHERE card_id = ?", 'i', [$cardId]);
    $fieldsById = [];
    foreach ($fields as $key => $field) $fieldsById[(int)$field['id']] = $key;
    $values = [];
    foreach ($rows as $row) {
        $fieldId = (int)$row['field_id'];
        if (!isset($fieldsById[$fieldId])) continue;
        $key = $fieldsById[$fieldId];
        $type = $fields[$key]['field_type'];
        if ($type === 'number') $values[$key] = $row['value_number'] === null ? null : (float)$row['value_number'];
        elseif ($type === 'boolean') $values[$key] = $row['value_boolean'];
        elseif ($type === 'multiselect') $values[$key] = $row['value_json'] ? json_decode($row['value_json'], true) : [];
        else $values[$key] = $row['value_text'];
    }
    return $values;
}

function transcribeWithCodexCli($imageUrl, $cardId, $model) {
    $codexPath = resolveCodexCliPath();
    $tempDir = sys_get_temp_dir();
    $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
    if ($extension === '' || !preg_match('/^[a-z0-9]+$/', $extension)) $extension = 'jpg';
    $imagePath = $tempDir . DIRECTORY_SEPARATOR . 'tcgengine-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($cardId)) . '.' . $extension;
    $stderrPath = $tempDir . DIRECTORY_SEPARATOR . 'tcgengine-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($cardId)) . '-codex-stderr.log';
    file_put_contents($imagePath, fetchBinaryUrl($imageUrl));

    $schemaPath = ensureTranscriptionSchemaFile();
    $prompt = implode("\n", [
        "Transcribe this Grand Archive TCG card image into strict JSON.",
        "Read only printed card information. Do not infer missing values.",
        "Use null when a value is absent, hidden, or unreadable.",
        "For classes, read only the printed class line or class icons; do not copy the card type into classes.",
        "For champion cards, leave classes null unless a separate class is printed; never set classes to CHAMPION.",
        "For subtypes, read only printed subtypes.",
        "For type, use the card type, such as CHAMPION, ALLY, ACTION, ATTACK, ITEM, REGALIA, DOMAIN, or TOKEN.",
        "Champion cards usually have level and life but no memory or reserve cost unless a separate cost is clearly printed.",
        "For element and speed, use uppercase strings when present.",
        "For costs and stats, use numbers, not strings.",
        "For effect, include rules text and omit flavor text when you can distinguish it.",
        "Return only JSON matching the schema."
    ]);

    $command = [
        $codexPath,
        'exec',
        '--ignore-user-config',
        '--ephemeral',
        '--sandbox',
        'read-only',
        '--model',
        $model,
        '--output-schema',
        $schemaPath,
        '--image',
        $imagePath,
        '-'
    ];
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['file', $stderrPath, 'w']
    ];
    $process = proc_open($command, $descriptors, $pipes, __DIR__ . '/..', null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new Exception("Could not start Codex CLI");
    }
    fwrite($pipes[0], $prompt);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $exitCode = proc_close($process);
    $stderr = file_exists($stderrPath) ? file_get_contents($stderrPath) : '';
    if ($exitCode !== 0) {
        throw new Exception("Codex CLI exited with $exitCode: " . truncate(trim($stderr), 500));
    }

    $json = trim($stdout);
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        if (preg_match('/\{.*\}/s', $json, $match)) {
            $decoded = json_decode($match[0], true);
        }
    }
    if (!is_array($decoded)) {
        throw new Exception("Codex CLI did not return valid JSON: " . truncate($json, 500));
    }
    return normalizeTranscription($decoded);
}

function resolveCodexCliPath() {
    if (getenv('CODEX_CLI_PATH')) return getenv('CODEX_CLI_PATH');
    $localAppData = getenv('LOCALAPPDATA');
    if ($localAppData) {
        $candidate = $localAppData . DIRECTORY_SEPARATOR . 'OpenAI' . DIRECTORY_SEPARATOR . 'Codex' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'codex.exe';
        if (file_exists($candidate)) return $candidate;
    }
    return 'codex';
}

function ensureTranscriptionSchemaFile() {
    static $schemaPath = null;
    if ($schemaPath && file_exists($schemaPath)) return $schemaPath;
    $schema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => ['string', 'null']],
            'element' => ['type' => ['string', 'null']],
            'type' => ['type' => ['string', 'null']],
            'speed' => ['type' => ['string', 'null']],
            'cost_memory' => ['type' => ['number', 'null']],
            'cost_reserve' => ['type' => ['number', 'null']],
            'level' => ['type' => ['number', 'null']],
            'power' => ['type' => ['number', 'null']],
            'life' => ['type' => ['number', 'null']],
            'durability' => ['type' => ['number', 'null']],
            'classes' => ['type' => ['array', 'null'], 'items' => ['type' => 'string']],
            'subtypes' => ['type' => ['array', 'null'], 'items' => ['type' => 'string']],
            'effect' => ['type' => ['string', 'null']]
        ],
        'required' => transcriptionFieldKeys(),
        'additionalProperties' => false
    ];
    $schemaPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcgengine-grand-archive-transcription-schema.json';
    file_put_contents($schemaPath, json_encode($schema, JSON_PRETTY_PRINT));
    return $schemaPath;
}

function normalizeTranscription($decoded) {
    $normalized = [];
    foreach (transcriptionFieldKeys() as $key) {
        $normalized[$key] = $decoded[$key] ?? null;
    }
    foreach (['element', 'type', 'speed'] as $key) {
        if (is_string($normalized[$key])) $normalized[$key] = strtoupper(normalizeWhitespace($normalized[$key]));
    }
    foreach (['classes', 'subtypes'] as $key) {
        if (is_string($normalized[$key])) {
            $normalized[$key] = array_values(array_filter(array_map('trim', preg_split('/[,;\/]+/', $normalized[$key]))));
        }
        if (is_array($normalized[$key])) {
            $normalized[$key] = array_values(array_filter(array_map(function($value) {
                return strtoupper(normalizeWhitespace($value));
            }, $normalized[$key]), function($value) {
                return $value !== '';
            }));
        }
    }
    foreach (['cost_memory', 'cost_reserve', 'level', 'power', 'life', 'durability'] as $key) {
        $normalized[$key] = is_numeric($normalized[$key]) ? (float)$normalized[$key] : null;
    }
    if (is_array($normalized['classes'])) {
        $type = $normalized['type'];
        $normalized['classes'] = array_values(array_filter($normalized['classes'], function($class) use ($type) {
            return $class !== $type && $class !== 'CHAMPION';
        }));
    }
    if ($normalized['type'] === 'CHAMPION') {
        $normalized['cost_memory'] = null;
        $normalized['cost_reserve'] = null;
        $normalized['power'] = null;
        $normalized['durability'] = null;
    }
    foreach (['name', 'effect'] as $key) {
        if (is_string($normalized[$key])) $normalized[$key] = normalizeWhitespace($normalized[$key]);
    }
    return $normalized;
}

function applyTranscription($conn, $cardId, $fields, $result, $now, $overwrite) {
    $currentValues = cardValuesByFieldKey($conn, $cardId, $fields);
    foreach (transcriptionFieldKeys() as $key) {
        if (!isset($fields[$key])) continue;
        $value = $result[$key] ?? null;
        if (emptyValue($value)) {
            if ($overwrite && $key !== 'name') deleteFieldValue($conn, $cardId, (int)$fields[$key]['id']);
            continue;
        }
        if (!$overwrite && !emptyValue($currentValues[$key] ?? null)) continue;
        if ($fields[$key]['field_type'] === 'number') {
            upsertNumberValue($conn, $cardId, (int)$fields[$key]['id'], $value, $now);
        } else {
            if (is_array($value)) $value = implode(', ', $value);
            upsertTextValue($conn, $cardId, (int)$fields[$key]['id'], (string)$value, $now);
        }
    }
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

function upsertNumberValue($conn, $cardId, $fieldId, $value, $now) {
    execStmt(
        $conn,
        "INSERT INTO ce_card_field_values (card_id, field_id, value_text, value_number, value_boolean, value_json, updated_at) VALUES (?, ?, NULL, ?, NULL, NULL, ?) ON DUPLICATE KEY UPDATE value_text = NULL, value_number = VALUES(value_number), value_boolean = NULL, value_json = NULL, updated_at = VALUES(updated_at)",
        'iids',
        [$cardId, $fieldId, (float)$value, $now]
    );
}

function deleteFieldValue($conn, $cardId, $fieldId) {
    execStmt($conn, "DELETE FROM ce_card_field_values WHERE card_id = ? AND field_id = ?", 'ii', [$cardId, $fieldId]);
}
