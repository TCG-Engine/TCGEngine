<?php
/** CLI-only, revision-checked card source migration. Defaults to a read-only preview. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../CardEditor/Database/CardAbilityRepository.php';

try {
    $path = $argv[1] ?? '';
    if (!is_file($path)) throw new RuntimeException('Usage: php DevTools/ApplyCardCodeMigration.php <migration.json> [--apply]');
    $migration = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $root = $migration['root'];
    $repo = OpenCardAbilityRepository($root);
    if (!($repo instanceof RemoteCardAbilityRepository)) throw new RuntimeException('Configure the hosted card connection before running this migration.');
    $byCard = [];
    foreach ($migration['patches'] as $patch) $byCard[$patch['card_id']][] = $patch;
    $pending = [];
    // Validate every expected source before making the first write.
    foreach ($byCard as $cardId => $patches) {
        $loaded = $repo->loadCardWithRevision($root, $cardId);
        $rows = $loaded['abilities'];
        $changed = false;
        foreach ($patches as $patch) {
            $matches = array_keys(array_filter($rows, fn($row) => $row['macro_name'] === $patch['macro_name']));
            if (count($matches) !== 1) throw new RuntimeException("Expected exactly one {$patch['macro_name']} row for $cardId");
            $index = $matches[0];
            foreach ($patch['fields'] as $field => $edit) {
                if (!in_array($field, ['ability_code', 'prereq_code'], true)) throw new RuntimeException('Unsupported migration field');
                if ($rows[$index][$field] === $edit['after']) continue;
                if ($rows[$index][$field] !== $edit['before']) throw new RuntimeException("Source changed for $cardId / {$patch['macro_name']} / $field; reconcile manually.");
                $rows[$index][$field] = $edit['after'];
                $changed = true;
            }
        }
        if (!$changed) { echo "$cardId: already applied\n"; continue; }
        $abilities = [];
        foreach ($rows as $row) {
            if ($row['macro_name'] === '' || $row['ability_code'] === '') throw new RuntimeException("Unexpected blank ability in $cardId; preserve manually.");
            $abilities[] = [
                'macroName' => $row['macro_name'], 'abilityCode' => $row['ability_code'],
                'prereqCode' => $row['prereq_code'], 'abilityName' => $row['ability_name'],
                'abilityType' => $row['ability_type'], 'listenerZones' => $row['listener_zones'],
                'isImplemented' => (bool)$row['is_implemented'],
            ];
        }
        $pending[$cardId] = ['abilities' => $abilities, 'revision' => $loaded['revision']];
        echo "$cardId: ready\n";
    }
    if (!in_array('--apply', $argv, true)) { echo 'Preview: ' . count($pending) . " cards; no writes.\n"; exit; }
    foreach ($pending as $cardId => $change) {
        $repo->replaceCardAbilities($root, $cardId, $change['abilities'], false, $change['revision']);
        echo "$cardId: saved\n";
    }
    echo "Regenerate GrandArchiveSim and run its regression suite.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
