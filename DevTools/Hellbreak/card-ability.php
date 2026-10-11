<?php

// Read and write a card's ability rows from the CLI, without the CardEditor browser UI.
//
//   php DevTools/Hellbreak/card-ability.php --card=DOT_049
//   php DevTools/Hellbreak/card-ability.php --card=DOT_049 --set=abilities.json
//   php DevTools/Hellbreak/card-ability.php --card=DOT_049 --set=abilities.json --no-generate
//
// Card abilities are rows in the MySQL `card_abilities` table, not files in the repo, so an agent
// working headlessly has no way to author one — `CardEditor/UI/index.html` is a browser page and
// the MCP server needs its own DB config. This is the same repository the editor and the MCP
// server write through (OpenCardAbilityRepository), so a row written here is identical to one
// saved from the UI.
//
// --set replaces the card's WHOLE ability list, matching the editor's save semantics. The JSON is
// the same array the editor posts:
//
//   [
//     {
//       "macroName": "Played",
//       "abilityType": "macro",              // or "listener" (then listenerZones is required)
//       "abilityName": "Initiative Blood Drain",
//       "abilityCode": "$opponent = HellbreakOtherPlayer($player);\nHellbreakLoseBlood($opponent, 1);",
//       "prereqCode": "return intval(GetInitiativePlayer()) === intval($player);",
//       "listenerZones": [],
//       "isImplemented": true
//     }
//   ]
//
// After a successful write the game code generator runs (so GeneratedMacroCode.php matches the DB)
// and the result is lint-checked. If either fails the previous rows are restored, so a broken
// ability never survives the call.
//
// Run it inside the Hellbreak container, from the repo root:
//   docker exec -w /var/www/html/TCGEngine otmtcge-hellbreaksim-web-server-1 \
//     php DevTools/Hellbreak/card-ability.php --card=DOT_049

declare(strict_types=1);

$repoRoot = dirname(__DIR__, 2);
chdir($repoRoot);

$args = getopt('', ['card::', 'set::', 'root::', 'no-generate', 'help']);
if (isset($args['help']) || empty($args['card'])) {
    echo "Usage: php DevTools/Hellbreak/card-ability.php --card=DOT_049 [--set=<abilities.json>] [--root=HellbreakSim] [--no-generate]\n";
    exit(isset($args['help']) ? 0 : 1);
}

$rootName = (string)($args['root'] ?? 'HellbreakSim');
if (!preg_match('/^[A-Za-z0-9_-]+$/', $rootName)) {
    fwrite(STDERR, "Bad root name.\n");
    exit(1);
}
$GLOBALS['rootName'] = $rootName; // DatabaseResolution uses this to pick the database on CLI.

require_once $repoRoot . '/CardEditor/Database/CardAbilityRepository.php';
require_once $repoRoot . '/Core/CardBaseMap.php';

// Abilities live on the base card, so a borderless/poster/alt-art number lands on its base.
$resolution = CardBaseResolution($rootName, (string)$args['card']);
$cardId = $resolution['cardId'];
if (!empty($resolution['isVariant'])) {
    fwrite(STDERR, "Note: {$resolution['requestedCardId']} is a variant printing; using base card {$cardId}.\n");
}

$db = OpenCardAbilityRepository($rootName);

function ShowCard($db, string $rootName, string $cardId, array $resolution): void
{
    $abilities = $db->loadCardAbilities($rootName, $cardId);
    echo json_encode([
        'abilities' => $abilities,
        'revision' => $db->revisionForCard($rootName, $cardId),
    ] + $resolution, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

if (empty($args['set'])) {
    ShowCard($db, $rootName, $cardId, $resolution);
    $db->close();
    exit(0);
}

$payloadPath = (string)$args['set'];
if (!is_file($payloadPath)) {
    fwrite(STDERR, "No such file: {$payloadPath}\n");
    exit(1);
}
$payload = json_decode((string)file_get_contents($payloadPath), true);
if (!is_array($payload)) {
    fwrite(STDERR, "{$payloadPath} is not a JSON array of abilities.\n");
    exit(1);
}
// Accept either a bare array or the editor's {"abilities": [...]} envelope.
$abilities = array_values(isset($payload['abilities']) && is_array($payload['abilities']) ? $payload['abilities'] : $payload);

$previous = $db->loadCardAbilities($rootName, $cardId);

try {
    $saved = $db->replaceCardAbilities($rootName, $cardId, $abilities, count($abilities) > 0, null);
} catch (Throwable $error) {
    fwrite(STDERR, 'Save failed: ' . $error->getMessage() . "\n");
    $db->close();
    exit(1);
}

echo 'Saved ' . count($saved['abilities'] ?? []) . " ability row(s) for {$cardId}.\n";

if (isset($args['no-generate'])) {
    $db->close();
    exit(0);
}

// Regenerate, then prove the generated file still parses. A row that compiles to broken PHP takes
// the whole engine down, so restore the previous rows rather than leave that on disk.
$generated = $repoRoot . '/' . $rootName . '/GeneratedCode/GeneratedMacroCode.php';
$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($repoRoot . '/zzGameCodeGenerator.php')
    . ' rootName=' . escapeshellarg($rootName) . ' 2>&1';
$output = (string)shell_exec($command);
$lint = (string)shell_exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($generated) . ' 2>&1');
$ok = str_contains($output, 'completed successfully') && str_contains($lint, 'No syntax errors');

if (!$ok) {
    fwrite(STDERR, "Generation or lint FAILED — rolling back {$cardId}.\n" . $output . "\n" . $lint . "\n");
    try {
        $restore = [];
        foreach ($previous as $row) {
            if (trim((string)($row['macro_name'] ?? '')) === '') continue;
            $restore[] = [
                'macroName' => $row['macro_name'],
                'abilityType' => $row['ability_type'] ?? 'macro',
                'abilityCode' => $row['ability_code'] ?? '',
                'prereqCode' => $row['prereq_code'] ?? null,
                'abilityName' => $row['ability_name'] ?? null,
                'listenerZones' => array_values(array_filter(explode(',', (string)($row['listener_zones'] ?? '')))),
                'isImplemented' => !empty($row['is_implemented']),
            ];
        }
        $db->replaceCardAbilities($rootName, $cardId, $restore, count($restore) > 0, null);
        shell_exec($command);
        fwrite(STDERR, "Rolled back to the previous " . count($restore) . " row(s).\n");
    } catch (Throwable $error) {
        fwrite(STDERR, 'ROLLBACK ALSO FAILED: ' . $error->getMessage() . " — fix card_abilities by hand.\n");
    }
    $db->close();
    exit(1);
}

echo "Regenerated {$rootName}/GeneratedCode/GeneratedMacroCode.php (lint clean).\n";
ShowCard($db, $rootName, $cardId, $resolution);
$db->close();
