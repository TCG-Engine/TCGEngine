<?php

declare(strict_types=1);

set_time_limit(10800);

$GLOBALS['rootName'] = 'GrandArchiveSim';

require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../CardEditor/Database/CardAuthoringDB.php';
require_once __DIR__ . '/../CardEditor/Database/CardAbilityDB.php';

const GA_SPOILER_PREFIX = 'GA-SHOUT-';
const GA_ROOT_NAME = 'GrandArchiveSim';
const GA_CARD_EDITOR_GAME_SLUG = 'grand-archive-sim';
const GA_CARD_EDITOR_SET_SLUG = 'grand-archive-spoilers';

function gaSpoilerMigrationIsApply(): bool
{
    if (PHP_SAPI === 'cli') {
        return in_array('--apply', $GLOBALS['argv'] ?? [], true);
    }
    return isset($_GET['apply']) && in_array(strtolower((string)$_GET['apply']), ['1', 'true', 'yes'], true);
}

function gaSpoilerMigrationRequireLocalHttp(): void
{
    if (PHP_SAPI === 'cli') return;
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (in_array($remote, ['127.0.0.1', '::1'], true)) return;
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'This migration is only available from localhost.']);
    exit;
}

function gaNormalizeIdentity(string $value): string
{
    $value = strtolower(trim($value));
    return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
}

/** @return array<int, object> */
function gaLoadOfficialCards(): array
{
    $cachePath = __DIR__ . '/../GrandArchiveSim/GeneratedCode/cardArrayCache.json';
    if (!is_file($cachePath)) {
        throw new RuntimeException('Missing GrandArchiveSim cardArrayCache.json; refresh official data first.');
    }
    $decoded = json_decode((string)file_get_contents($cachePath));
    if (!is_object($decoded) || !isset($decoded->cardArray) || !is_array($decoded->cardArray)) {
        throw new RuntimeException('GrandArchiveSim cardArrayCache.json has an invalid shape.');
    }
    return $decoded->cardArray;
}

/** @return array{byEditionSlug: array<string, object>, byName: array<string, array<int, object>>, byId: array<string, object>} */
function gaIndexOfficialCards(array $cards): array
{
    $byEditionSlug = [];
    $byName = [];
    $byId = [];
    foreach ($cards as $card) {
        if (!is_object($card)) continue;
        $cardId = trim((string)($card->uuid ?? $card->id ?? ''));
        if ($cardId === '' || str_starts_with($cardId, GA_SPOILER_PREFIX)) continue;
        $byId[$cardId] = $card;
        $nameKey = gaNormalizeIdentity((string)($card->name ?? ''));
        if ($nameKey !== '') $byName[$nameKey][] = $card;
        $editions = [];
        if (isset($card->editions) && is_array($card->editions)) $editions = array_merge($editions, $card->editions);
        if (isset($card->result_editions) && is_array($card->result_editions)) $editions = array_merge($editions, $card->result_editions);
        foreach ($editions as $edition) {
            if (!is_object($edition)) continue;
            $slug = strtolower(trim((string)($edition->slug ?? '')));
            if ($slug !== '') $byEditionSlug[$slug] = $card;
        }
    }
    return compact('byEditionSlug', 'byName', 'byId');
}

function gaFetchAll(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new RuntimeException(mysqli_error($conn));
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException($error);
    }
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    return $rows;
}

function gaExecute(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new RuntimeException(mysqli_error($conn));
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException($error);
    }
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

function gaLoadSpoilerCards(mysqli $conn): array
{
    return gaFetchAll(
        $conn,
        "SELECT c.id, c.name, c.slug, c.template_id, v.value_text AS old_card_id
           FROM ce_cards c
           JOIN ce_games g ON g.id = c.game_id
           JOIN ce_sets s ON s.id = c.set_id
           JOIN ce_template_fields f ON f.template_id = c.template_id AND f.field_key = 'uuid'
           JOIN ce_card_field_values v ON v.card_id = c.id AND v.field_id = f.id
          WHERE g.slug = ? AND s.slug = ? AND v.value_text LIKE 'GA-SHOUT-%'
          ORDER BY c.id",
        'ss',
        [GA_CARD_EDITOR_GAME_SLUG, GA_CARD_EDITOR_SET_SLUG]
    );
}

function gaLoadSupplementCardIds(mysqli $conn): array
{
    $rows = gaFetchAll(
        $conn,
        "SELECT DISTINCT v.value_text AS card_id
           FROM ce_cards c
           JOIN ce_games g ON g.id = c.game_id
           JOIN ce_sets s ON s.id = c.set_id
           JOIN ce_template_fields f ON f.template_id = c.template_id AND f.field_key = 'uuid'
           JOIN ce_card_field_values v ON v.card_id = c.id AND v.field_id = f.id
          WHERE g.slug = ? AND s.slug = ? AND v.value_text IS NOT NULL AND v.value_text <> ''",
        'ss',
        [GA_CARD_EDITOR_GAME_SLUG, GA_CARD_EDITOR_SET_SLUG]
    );
    return array_values(array_unique(array_column($rows, 'card_id')));
}

function gaFindEditionForOldId(object $card, string $oldId): ?object
{
    $wanted = strtolower(substr($oldId, strlen(GA_SPOILER_PREFIX)));
    foreach (['editions', 'result_editions'] as $property) {
        foreach (($card->$property ?? []) as $edition) {
            if (is_object($edition) && strtolower((string)($edition->slug ?? '')) === $wanted) return $edition;
        }
    }
    return null;
}

function gaBuildIdentityPlan(array $spoilers, array $index): array
{
    // The spoiler source misspelled Arclight as "Archlight" in both printing slugs.
    // Keep this explicit rather than allowing fuzzy matching to choose a card silently.
    $explicitOfficialIds = [
        'GA-SHOUT-LORRAINE-ARCHLIGHT-SABER-PRDSD' => 'x9sSpjpP3G',
        'GA-SHOUT-LORRAINE-ARCHLIGHT-SABER-PRD1E-CUR' => 'x9sSpjpP3G',
    ];
    $mappings = [];
    $unresolved = [];
    foreach ($spoilers as $spoiler) {
        $oldId = (string)$spoiler['old_card_id'];
        $editionSlug = strtolower(substr($oldId, strlen(GA_SPOILER_PREFIX)));
        $official = $index['byEditionSlug'][$editionSlug] ?? null;
        $matchedBy = 'edition-slug';
        if (!$official && isset($explicitOfficialIds[$oldId])) {
            $official = $index['byId'][$explicitOfficialIds[$oldId]] ?? null;
            $matchedBy = 'explicit-reviewed-override';
        }
        if (!$official) {
            $nameMatches = $index['byName'][gaNormalizeIdentity((string)$spoiler['name'])] ?? [];
            $uniqueById = [];
            foreach ($nameMatches as $candidate) {
                $candidateId = (string)($candidate->uuid ?? $candidate->id ?? '');
                if ($candidateId !== '') $uniqueById[$candidateId] = $candidate;
            }
            if (count($uniqueById) === 1) {
                $official = reset($uniqueById);
                $matchedBy = 'normalized-name';
            }
        }
        if (!$official) {
            $unresolved[] = ['oldId' => $oldId, 'name' => $spoiler['name'], 'editionSlug' => $editionSlug];
            continue;
        }
        $newId = trim((string)($official->uuid ?? $official->id ?? ''));
        if ($newId === '') {
            $unresolved[] = ['oldId' => $oldId, 'name' => $spoiler['name'], 'editionSlug' => $editionSlug];
            continue;
        }
        $mappings[$oldId] = [
            'oldId' => $oldId,
            'newId' => $newId,
            'oldName' => (string)$spoiler['name'],
            'officialName' => (string)($official->name ?? ''),
            'matchedBy' => $matchedBy,
            'cardEditorId' => (int)$spoiler['id'],
            'templateId' => (int)$spoiler['template_id'],
            'officialCard' => $official,
            'edition' => gaFindEditionForOldId($official, $oldId),
        ];
    }
    return ['mappings' => $mappings, 'unresolved' => $unresolved];
}

function gaReplacementArrays(array $mappings): array
{
    uksort($mappings, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    $search = [];
    $replace = [];
    foreach ($mappings as $oldId => $mapping) {
        $search[] = $oldId;
        $replace[] = $mapping['newId'];
    }
    // One turn-effect key predated the printing-specific spoiler IDs and therefore is
    // not a direct card identity. Migrate it alongside the canonical card references.
    $search[] = 'GA-SHOUT-LORRAINE-HONED-OPERATIVE_POWER';
    $replace[] = 'UsX7t4lXfX_POWER';
    return [$search, $replace];
}

function gaPrepareSourceChanges(array $mappings): array
{
    [$search, $replace] = gaReplacementArrays($mappings);
    $relativePaths = [
        'GrandArchiveSim/Custom/GameLogic.php',
        'GrandArchiveSim/Custom/CombatLogic.php',
        'GrandArchiveSim/Custom/MaterializeLogic.php',
        'GrandArchiveSim/Custom/CardDQHandlers.php',
    ];
    $testRoot = __DIR__ . '/../Tests/Integration/GrandArchiveSim';
    if (is_dir($testRoot)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['json', 'php', 'txt'], true)) continue;
            $relativePaths[] = str_replace('\\', '/', substr($file->getPathname(), strlen(__DIR__ . '/../')));
        }
    }
    $changes = [];
    foreach ($relativePaths as $relativePath) {
        $path = __DIR__ . '/../' . $relativePath;
        $original = (string)file_get_contents($path);
        $updated = str_replace($search, $replace, $original, $replacementCount);
        if ($replacementCount === 0) continue;
        $changes[] = [
            'path' => $path,
            'relativePath' => $relativePath,
            'original' => $original,
            'updated' => $updated,
            'replacementCount' => $replacementCount,
        ];
    }
    return $changes;
}

function gaWriteSourceChanges(array $changes, string $contentKey): void
{
    foreach ($changes as $change) {
        $path = $change['path'];
        $temporaryPath = $path . '.ga-spoiler-migration.tmp';
        if (file_put_contents($temporaryPath, $change[$contentKey]) === false || !rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Could not update ' . $change['relativePath']);
        }
    }
}

function gaTransformAbilityRows(array $rows, array $mappings): array
{
    [$search, $replace] = gaReplacementArrays($mappings);
    $transformed = [];
    foreach ($rows as $row) {
        $oldCardId = (string)$row['card_id'];
        $row['origin_card_id'] = $oldCardId;
        $row['card_id'] = $mappings[$oldCardId]['newId'] ?? $oldCardId;
        foreach (['ability_code', 'prereq_code'] as $field) {
            if ($row[$field] !== null && $row[$field] !== '') {
                $row[$field] = str_replace($search, $replace, (string)$row[$field]);
            }
        }
        $transformed[] = $row;
    }
    return $transformed;
}

function gaAbilityPayloadSignature(array $row): string
{
    $fields = ['card_id', 'macro_name', 'ability_type', 'ability_code', 'prereq_code', 'listener_zones', 'ability_name', 'is_implemented'];
    $payload = [];
    foreach ($fields as $field) $payload[$field] = $row[$field] ?? null;
    return hash('sha256', json_encode($payload));
}

function gaAbilityRuntimeSignature(array $row): string
{
    $payload = [
        'card_id' => $row['card_id'],
        'macro_name' => $row['macro_name'],
        'ability_type' => $row['ability_type'],
        'ability_code' => (string)($row['ability_code'] ?? ''),
        'prereq_code' => (string)($row['prereq_code'] ?? ''),
        'listener_zones' => (string)($row['listener_zones'] ?? ''),
    ];
    return hash('sha256', json_encode($payload));
}

function gaAuditAbilityCollisions(array $rows, array $mappings): array
{
    // The two Dante spoiler printings were coded separately. The CSR row includes the
    // required ready-state prerequisite, so it is the reviewed canonical implementation.
    $preferredOrigins = [
        '4FtNBFaOJp|ActivateAbility|macro' => 'GA-SHOUT-DANTE-HEMOMANCER-PRD1E-CSR',
    ];
    $mappedIds = array_fill_keys(array_column($mappings, 'newId'), true);
    $groups = [];
    foreach ($rows as $row) {
        if (!isset($mappedIds[$row['card_id']])) continue;
        $key = implode('|', [$row['card_id'], $row['macro_name'], $row['ability_type']]);
        $groups[$key][] = $row;
    }

    $replaceIds = [];
    $resolved = [];
    $conflicts = [];
    foreach ($groups as $key => $group) {
        $origins = array_values(array_unique(array_column($group, 'origin_card_id')));
        if (count($origins) < 2) continue;
        $spoilerOrigins = array_values(array_filter($origins, static fn(string $origin): bool => str_starts_with($origin, GA_SPOILER_PREFIX)));
        $preferredOrigin = $preferredOrigins[$key] ?? null;
        if ($preferredOrigin === null && count($spoilerOrigins) === 1) {
            // A newly coded spoiler implementation supersedes an old implementation or the
            // unimplemented placeholder created when the official UUID first appeared.
            $preferredOrigin = $spoilerOrigins[0];
        }
        if ($preferredOrigin === null && count($spoilerOrigins) > 1) {
            $runtimeSignatures = [];
            foreach ($group as $row) {
                if (in_array($row['origin_card_id'], $spoilerOrigins, true)) {
                    $runtimeSignatures[gaAbilityRuntimeSignature($row)] = true;
                }
            }
            if (count($runtimeSignatures) === 1) $preferredOrigin = $spoilerOrigins[0];
        }
        if ($preferredOrigin === null || !in_array($preferredOrigin, $origins, true)) {
            $conflicts[] = [
                'key' => $key,
                'origins' => $origins,
                'rows' => array_map(static fn(array $row): array => [
                    'id' => (int)$row['id'],
                    'originCardId' => $row['origin_card_id'],
                    'abilityName' => $row['ability_name'],
                    'implemented' => (bool)$row['is_implemented'],
                    'payloadHash' => substr(gaAbilityPayloadSignature($row), 0, 12),
                ], $group),
            ];
            continue;
        }
        $deleted = [];
        foreach ($group as $row) {
            if ($row['origin_card_id'] === $preferredOrigin) continue;
            $replaceIds[] = (int)$row['id'];
            $deleted[] = (int)$row['id'];
        }
        $resolved[] = ['key' => $key, 'preferredOrigin' => $preferredOrigin, 'deletedRowIds' => $deleted];
    }
    return ['replaceIds' => array_values(array_unique($replaceIds)), 'resolved' => $resolved, 'conflicts' => $conflicts];
}

function gaOfficialFieldValues(array $mapping): array
{
    $card = $mapping['officialCard'];
    $edition = $mapping['edition'];
    $image = $edition && isset($edition->image) ? (string)$edition->image : '';
    if ($image !== '' && !preg_match('/^https?:\/\//i', $image)) $image = 'https://api.gatcg.com/' . ltrim($image, '/');
    $setPrefix = $edition && isset($edition->set->prefix) ? (string)$edition->set->prefix : '';
    return [
        'uuid' => $mapping['newId'],
        'name' => (string)($card->name ?? ''),
        'element' => (string)($card->element ?? ''),
        'type' => is_array($card->types ?? null) ? implode(',', $card->types) : (string)($card->type ?? ''),
        'speed' => isset($card->speed) ? (string)$card->speed : null,
        'cost_memory' => $card->cost_memory ?? null,
        'cost_reserve' => $card->cost_reserve ?? null,
        'level' => $card->level ?? null,
        'power' => $card->power ?? null,
        'life' => $card->life ?? null,
        'durability' => $card->durability ?? null,
        'classes' => is_array($card->classes ?? null) ? implode(',', $card->classes) : (string)($card->classes ?? ''),
        'subtypes' => is_array($card->subtypes ?? null) ? implode(',', $card->subtypes) : (string)($card->subtypes ?? ''),
        'effect' => (string)($card->effect ?? ''),
        'set' => $setPrefix,
        'image_url' => $image,
    ];
}

function gaUpsertCardField(mysqli $conn, int $cardId, array $field, mixed $value, string $now): void
{
    $fieldId = (int)$field['id'];
    if ($value === null || $value === '') {
        gaExecute($conn, 'DELETE FROM ce_card_field_values WHERE card_id = ? AND field_id = ?', 'ii', [$cardId, $fieldId]);
        return;
    }
    if ($field['field_type'] === 'number') {
        gaExecute(
            $conn,
            'INSERT INTO ce_card_field_values (card_id, field_id, value_text, value_number, value_boolean, value_json, updated_at) VALUES (?, ?, NULL, ?, NULL, NULL, ?) ON DUPLICATE KEY UPDATE value_text = NULL, value_number = VALUES(value_number), value_boolean = NULL, value_json = NULL, updated_at = VALUES(updated_at)',
            'iids',
            [$cardId, $fieldId, (float)$value, $now]
        );
        return;
    }
    gaExecute(
        $conn,
        'INSERT INTO ce_card_field_values (card_id, field_id, value_text, value_number, value_boolean, value_json, updated_at) VALUES (?, ?, ?, NULL, NULL, NULL, ?) ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), value_number = NULL, value_boolean = NULL, value_json = NULL, updated_at = VALUES(updated_at)',
        'iiss',
        [$cardId, $fieldId, (string)$value, $now]
    );
}

function gaApplyMigration(mysqli $conn, array $mappings, array $abilityRows, array $deduplicateIds): array
{
    $now = date('Y-m-d H:i:s');
    $deletedAbilityIds = array_fill_keys($deduplicateIds, true);
    foreach ($abilityRows as $row) {
        if (isset($deletedAbilityIds[(int)$row['id']])) continue;
        gaExecute(
            $conn,
            'UPDATE card_abilities SET card_id = ?, ability_code = ?, prereq_code = ? WHERE id = ? AND root_name = ?',
            'sssis',
            [$row['card_id'], $row['ability_code'], $row['prereq_code'], (int)$row['id'], GA_ROOT_NAME]
        );
    }
    foreach ($deduplicateIds as $id) {
        gaExecute($conn, 'DELETE FROM card_abilities WHERE id = ? AND root_name = ?', 'is', [(int)$id, GA_ROOT_NAME]);
    }

    $groups = [];
    foreach ($mappings as $mapping) $groups[$mapping['newId']][] = $mapping;
    $mergedCardEditorRows = 0;
    foreach ($groups as $newId => $group) {
        usort($group, static fn(array $a, array $b): int => $a['cardEditorId'] <=> $b['cardEditorId']);
        $canonical = array_shift($group);
        $canonicalId = (int)$canonical['cardEditorId'];
        foreach ($group as $duplicate) {
            $duplicateId = (int)$duplicate['cardEditorId'];
            gaExecute($conn, 'INSERT IGNORE INTO ce_card_tags (card_id, tag_id, created_at) SELECT ?, tag_id, created_at FROM ce_card_tags WHERE card_id = ?', 'ii', [$canonicalId, $duplicateId]);
            gaExecute($conn, 'DELETE FROM ce_card_tags WHERE card_id = ?', 'i', [$duplicateId]);
            gaExecute($conn, 'DELETE FROM ce_card_field_values WHERE card_id = ?', 'i', [$duplicateId]);
            gaExecute($conn, 'DELETE FROM ce_cards WHERE id = ?', 'i', [$duplicateId]);
            ++$mergedCardEditorRows;
        }

        $fields = gaFetchAll($conn, 'SELECT id, field_key, field_type FROM ce_template_fields WHERE template_id = ?', 'i', [(int)$canonical['templateId']]);
        $fieldsByKey = [];
        foreach ($fields as $field) $fieldsByKey[$field['field_key']] = $field;
        $values = gaOfficialFieldValues($canonical);
        foreach ($values as $key => $value) {
            if (isset($fieldsByKey[$key])) gaUpsertCardField($conn, $canonicalId, $fieldsByKey[$key], $value, $now);
        }
        gaExecute(
            $conn,
            'UPDATE ce_cards SET name = ?, slug = ?, updated_at = ? WHERE id = ?',
            'sssi',
            [$canonical['officialName'], CardAuthoringDB::slugify($newId), $now, $canonicalId]
        );
    }

    return ['updatedAbilityRows' => count($abilityRows) - count($deduplicateIds), 'deduplicatedAbilityRows' => count($deduplicateIds), 'mergedCardEditorRows' => $mergedCardEditorRows];
}

function gaRemoveImplementedCardPlaceholders(mysqli $conn, array $cardIds): int
{
    $removed = 0;
    foreach ($cardIds as $cardId) {
        $removed += gaExecute(
            $conn,
            "DELETE placeholder
               FROM card_abilities placeholder
               JOIN card_abilities implemented
                 ON implemented.root_name = placeholder.root_name
                AND implemented.card_id = placeholder.card_id
                AND implemented.is_implemented = 1
                AND implemented.id <> placeholder.id
              WHERE placeholder.root_name = ?
                AND placeholder.card_id = ?
                AND placeholder.macro_name = ''
                AND placeholder.ability_code = ''
                AND COALESCE(placeholder.prereq_code, '') = ''
                AND placeholder.is_implemented = 0",
            'ss',
            [GA_ROOT_NAME, $cardId]
        );
    }
    return $removed;
}

gaSpoilerMigrationRequireLocalHttp();
$apply = gaSpoilerMigrationIsApply();

try {
    $officialCards = gaLoadOfficialCards();
    $index = gaIndexOfficialCards($officialCards);
    $conn = GetLocalMySQLConnection();
    if (!$conn) throw new RuntimeException('Could not connect to the GrandArchiveSim database.');
    new CardAuthoringDB($conn);
    new CardAbilityDB($conn);

    $spoilers = gaLoadSpoilerCards($conn);
    $supplementCardIds = gaLoadSupplementCardIds($conn);
    $plan = gaBuildIdentityPlan($spoilers, $index);
    $abilityRows = gaFetchAll(
        $conn,
        'SELECT id, card_id, macro_name, ability_type, ability_code, prereq_code, listener_zones, ability_name, is_implemented FROM card_abilities WHERE root_name = ? ORDER BY id',
        's',
        [GA_ROOT_NAME]
    );
    $transformedAbilities = gaTransformAbilityRows($abilityRows, $plan['mappings']);
    $audit = gaAuditAbilityCollisions($transformedAbilities, $plan['mappings']);
    $sourceChanges = gaPrepareSourceChanges($plan['mappings']);
    $remainingTemporaryAbilityCardIds = [];
    $remainingTemporaryAbilityCodeRows = [];
    foreach ($abilityRows as $abilityRow) {
        if (str_starts_with((string)$abilityRow['card_id'], GA_SPOILER_PREFIX)) {
            $remainingTemporaryAbilityCardIds[] = (string)$abilityRow['card_id'];
        }
        if (str_contains((string)($abilityRow['ability_code'] ?? ''), GA_SPOILER_PREFIX)
            || str_contains((string)($abilityRow['prereq_code'] ?? ''), GA_SPOILER_PREFIX)) {
            $remainingTemporaryAbilityCodeRows[] = (int)$abilityRow['id'];
        }
    }

    $alreadyMigrated = count($spoilers) === 0 && count($supplementCardIds) > 0;
    $ready = (count($spoilers) > 0 || $alreadyMigrated) && !$plan['unresolved'] && !$audit['conflicts'];
    $result = [
        'success' => true,
        'mode' => $apply ? 'apply' : 'dry-run',
        'ready' => $ready,
        'alreadyMigrated' => $alreadyMigrated,
        'officialCardCount' => count($officialCards),
        'spoilerCardCount' => count($spoilers),
        'mappedCount' => count($plan['mappings']),
        'officialIdentityCount' => count(array_unique(array_column($plan['mappings'], 'newId'))),
        'unresolved' => $plan['unresolved'],
        'abilityCollisionConflicts' => $audit['conflicts'],
        'resolvedAbilityCollisions' => $audit['resolved'],
        'abilityRowsToReplace' => $audit['replaceIds'],
        'remainingTemporaryAbilityCardIds' => array_values(array_unique($remainingTemporaryAbilityCardIds)),
        'remainingTemporaryAbilityCodeRows' => array_values(array_unique($remainingTemporaryAbilityCodeRows)),
        'sourceChanges' => array_map(static fn(array $change): array => [
            'path' => $change['relativePath'],
            'replacementCount' => $change['replacementCount'],
        ], $sourceChanges),
        'mappings' => array_map(static fn(array $mapping): array => [
            'oldId' => $mapping['oldId'],
            'newId' => $mapping['newId'],
            'oldName' => $mapping['oldName'],
            'officialName' => $mapping['officialName'],
            'matchedBy' => $mapping['matchedBy'],
        ], array_values($plan['mappings'])),
    ];

    if ($apply) {
        if (!$ready) throw new RuntimeException('Migration refused: resolve all identity and ability collisions first.');
        mysqli_begin_transaction($conn);
        try {
            $result['applied'] = gaApplyMigration($conn, $plan['mappings'], $transformedAbilities, $audit['replaceIds']);
            $result['applied']['removedUnimplementedPlaceholders'] = gaRemoveImplementedCardPlaceholders($conn, $supplementCardIds);
            gaWriteSourceChanges($sourceChanges, 'updated');
            mysqli_commit($conn);
        } catch (Throwable $error) {
            mysqli_rollback($conn);
            gaWriteSourceChanges($sourceChanges, 'original');
            throw $error;
        }
    }
    mysqli_close($conn);

    if (PHP_SAPI !== 'cli') header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $error) {
    if (isset($conn) && $conn instanceof mysqli) mysqli_close($conn);
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'mode' => $apply ? 'apply' : 'dry-run', 'error' => $error->getMessage()], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}
