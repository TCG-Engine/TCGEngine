<?php
/**
 * Applies a card-ability patch (a JSON list of ops) to the CardEditor ability database, through the
 * same repository the CardEditor uses -- so it works against a local MySQL database or a configured
 * remote CardCodeService alike.
 *
 *   php DevTools/card-db-patches/apply-card-ability-patch.php --patch=<file.json>            # dry run
 *   php DevTools/card-db-patches/apply-card-ability-patch.php --patch=<file.json> --apply    # write
 *
 * Every op is idempotent and reports one of:
 *   APPLIED  the database already has the intended state (nothing to do)
 *   PENDING  the database has the old state and the op will change it (dry run: would change)
 *   BLOCKED  the database matches neither state (card missing, or rows edited since); never written
 *   FIXED    --apply wrote the change and a re-read confirmed it
 *   FAILED   --apply tried and the write or the re-read check failed
 * Exit status is 0 unless an op is BLOCKED or FAILED.
 *
 * After a real --apply, regenerate the engine so the change reaches the generated macro code:
 *   php zzGameCodeGenerator.php rootName=<root>
 *
 * Op types (see README.md in this folder):
 *   set-macro         {cardId, fromMacro, toMacro}              move a card's ability to another macro table
 *   replace-text      {cardId, from, to}                        replace a string inside ability_code
 *   author-if-absent  {cardId, abilities:[{macroName, ...}]}    add rows to a card that has none
 */

function CardAbilityPatchRowToAbility(array $row): array
{
    return [
        'macroName' => (string)$row['macro_name'],
        'abilityCode' => (string)$row['ability_code'],
        'prereqCode' => $row['prereq_code'] ?? null,
        'abilityName' => $row['ability_name'] ?? null,
        'isImplemented' => (int)($row['is_implemented'] ?? 0),
        'abilityType' => (string)($row['ability_type'] ?? 'macro'),
        'listenerZones' => $row['listener_zones'] ?? null,
    ];
}

// Classify one op against the card's current rows. Returns [state, note].
function CardAbilityPatchClassify(array $rows, array $op): array
{
    switch ($op['type']) {
        case 'set-macro':
            foreach ($rows as $row) if ($row['macro_name'] === $op['fromMacro']) return ['PENDING', "'{$op['fromMacro']}' -> '{$op['toMacro']}'"];
            foreach ($rows as $row) if ($row['macro_name'] === $op['toMacro']) return ['APPLIED', "already under '{$op['toMacro']}'"];
            return ['BLOCKED', "no ability under '{$op['fromMacro']}' or '{$op['toMacro']}'"];
        case 'replace-text':
            foreach ($rows as $row) if (strpos((string)$row['ability_code'], $op['from']) !== false) return ['PENDING', "'{$op['from']}' -> '{$op['to']}'"];
            foreach ($rows as $row) if (strpos((string)$row['ability_code'], $op['to']) !== false) return ['APPLIED', "code already contains '{$op['to']}'"];
            return ['BLOCKED', "no ability code contains '{$op['from']}' or '{$op['to']}'"];
        case 'author-if-absent':
            $real = array_values(array_filter($rows, fn($row) => (string)$row['macro_name'] !== ''));
            if (count($real) === 0) return ['PENDING', 'no ability rows yet; will author ' . count($op['abilities'])];
            foreach ($op['abilities'] as $want) {
                $found = false;
                foreach ($real as $row) if ($row['macro_name'] === $want['macroName'] && trim((string)$row['ability_code']) === trim($want['abilityCode'])) $found = true;
                if (!$found) return ['BLOCKED', 'card already has different ability rows; not overwriting'];
            }
            return ['APPLIED', 'authored rows already present'];
    }
    return ['BLOCKED', "unknown op type '{$op['type']}'"];
}

// Write one PENDING op. Throws on failure.
function CardAbilityPatchWrite($repo, string $root, array $op, array $rows): void
{
    switch ($op['type']) {
        case 'set-macro':
        case 'replace-text':
            foreach ($rows as $row) {
                $macro = $row['macro_name'];
                $code = (string)$row['ability_code'];
                if ($op['type'] === 'set-macro') {
                    if ($macro !== $op['fromMacro']) continue;
                    $macro = $op['toMacro'];
                } else {
                    if (strpos($code, $op['from']) === false) continue;
                    $code = str_replace($op['from'], $op['to'], $code);
                }
                $ok = $repo->saveAbility((int)$row['id'], $root, $op['cardId'], $macro, $code, $row['prereq_code'], $row['ability_name'],
                    (int)$row['is_implemented'], $row['ability_type'], $row['listener_zones']);
                if ($ok === false) throw new RuntimeException("saveAbility failed for ability id {$row['id']}");
            }
            return;
        case 'author-if-absent':
            $abilities = array_map(fn($a) => $a + ['prereqCode' => null, 'isImplemented' => 1, 'abilityType' => 'macro'], $op['abilities']);
            $repo->replaceCardAbilities($root, $op['cardId'], $abilities, true, $repo->revisionForCard($root, $op['cardId']));
            return;
    }
    throw new RuntimeException("unknown op type '{$op['type']}'");
}

// Run every op in $patch. Returns [results, ok] where each result is [id, cardId, state, note].
function RunCardAbilityPatch($repo, array $patch, bool $apply): array
{
    $root = (string)$patch['root'];
    $results = [];
    $ok = true;
    foreach ($patch['ops'] as $op) {
        $rows = $repo->loadCardAbilities($root, $op['cardId']);
        [$state, $note] = CardAbilityPatchClassify($rows, $op);
        if ($state === 'PENDING' && $apply) {
            try {
                CardAbilityPatchWrite($repo, $root, $op, $rows);
                $after = CardAbilityPatchClassify($repo->loadCardAbilities($root, $op['cardId']), $op);
                if ($after[0] === 'APPLIED') { $state = 'FIXED'; $note = 'written and verified by re-read'; }
                else { $state = 'FAILED'; $note = 'wrote, but a re-read did not show the intended state (' . $after[1] . ')'; }
            } catch (Throwable $error) {
                $state = 'FAILED';
                $note = ($error->getCode() === 409 ? 'conflict: the card changed while patching; rerun. ' : '') . $error->getMessage();
            }
        }
        if ($state === 'BLOCKED' || $state === 'FAILED') $ok = false;
        $results[] = [$op['id'] ?? $op['cardId'], $op['cardId'], $state, $note];
    }
    return [$results, $ok];
}

function CardAbilityPatchMain(array $argv): int
{
    $patchFile = '';
    $apply = false;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--apply') $apply = true;
        elseif (str_starts_with($arg, '--patch=')) $patchFile = substr($arg, 8);
    }
    if ($patchFile === '' || !is_file($patchFile)) {
        fwrite(STDERR, "Usage: php apply-card-ability-patch.php --patch=<file.json> [--apply]\n");
        return 2;
    }
    $patch = json_decode((string)file_get_contents($patchFile), true);
    if (!is_array($patch) || empty($patch['root']) || !is_array($patch['ops'] ?? null)) {
        fwrite(STDERR, "Patch file must be JSON with a 'root' and an 'ops' list.\n");
        return 2;
    }

    // A script under DevTools/ would otherwise be resolved as the app "DevTools". MYSQL_DATABASE_NAME, when
    // set, still wins (Database/DatabaseResolution.php).
    $GLOBALS['rootName'] = (string)$patch['root'];
    require_once dirname(__DIR__, 2) . '/CardEditor/Database/CardAbilityRepository.php';
    try {
        $repo = OpenCardAbilityRepository((string)$patch['root']);
    } catch (Throwable $error) {
        fwrite(STDERR, "Could not open the ability database for {$patch['root']}: " . $error->getMessage() . "\n");
        return 3;
    }
    echo ($apply ? 'APPLY' : 'DRY RUN') . ' -- ' . ($patch['description'] ?? basename($patchFile)) . " (root {$patch['root']})\n";
    [$results, $ok] = RunCardAbilityPatch($repo, $patch, $apply);
    foreach ($results as [$id, $cardId, $state, $note]) printf("  %-8s %-34s %s  %s\n", $state, $id, $cardId, $note);
    if (method_exists($repo, 'close')) $repo->close();

    $changed = count(array_filter($results, fn($r) => $r[2] === 'FIXED'));
    if ($apply && $changed > 0) echo "\nNow regenerate: php zzGameCodeGenerator.php rootName={$patch['root']}\n";
    if (!$apply && count(array_filter($results, fn($r) => $r[2] === 'PENDING')) > 0) echo "\nDry run only. Add --apply to write.\n";
    return $ok ? 0 : 1;
}

if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    exit(CardAbilityPatchMain($argv));
}
