<?php
// Card-identifier census — READ ONLY. Runs only SELECTs; writes nothing, changes nothing.
//
// Answers the questions that gate the SET_NNN identity migration
// (docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md):
//   1. Which stored identifiers cannot be mapped to a SET_NNN?  (a non-empty list blocks cutover)
//   2. How many rows collapse onto a shared key once reprints fold?  (the aggregation delta)
//   3. Which tables/columns actually exist on THIS box?  (prod has drifted from database.sql)
//
// Usage:  php DevTools/census-card-identifiers.php
//         php DevTools/census-card-identifiers.php --show-unmapped=50

require_once __DIR__ . '/../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../AppCore/SWU/Overrides.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';

$showUnmapped = 25;
foreach ($argv as $arg) {
    if (preg_match('/^--show-unmapped=(\d+)$/', $arg, $m)) $showUnmapped = (int)$m[1];
}

// (table, column) pairs holding a card identifier. Verified against information_schema below —
// anything absent on this box is reported as missing rather than fataling.
$TARGETS = [
    ['carddeckstats',          'cardID'],
    ['cardmetastats',          'cardID'],
    ['deckstats',              'leaderID'],
    ['opponentdeckstats',      'leaderID'],
    ['opponentnamedbasestats', 'leaderID'],
    ['opponentnamedbasestats', 'baseID'],
    ['deckmetastats',          'leaderID'],
    ['deckmetastats',          'baseID'],
    ['deckmetamatchupstats',   'leaderID'],
    ['deckmetamatchupstats',   'baseID'],
    ['deckmetamatchupstats',   'opponentLeaderID'],
    ['deckmetamatchupstats',   'opponentBaseID'],
    ['completedgame',          'WinningHero'],
    ['completedgame',          'LosingHero'],
    ['favoritedeck',           'hero'],
    ['favoritedeck',           'baseId'],
    ['meleetournamentdeck',    'leader'],
    ['meleetournamentdeck',    'base'],
    ['matchhistory',           'keyCard1ID'],
    ['matchhistory',           'keyCard2ID'],
    ['matchhistory',           'keyCard3ID'],
    ['matchhistory',           'opponentKeyCard1ID'],
    ['matchhistory',           'opponentKeyCard2ID'],
    ['matchhistory',           'opponentKeyCard3ID'],
];

// Already a SET_NNN / SET_NN / SET_T## identity? (matches the mock pipeline's own rule)
function census_is_set_nnn(string $v): bool {
    return (bool)preg_match('/^[A-Z0-9]{2,5}_(T\d{2}|\d{2,3})$/', $v);
}

$conn = GetLocalMySQLConnection();
$db = $conn->query('SELECT DATABASE()')->fetch_row()[0];

// Which of the targets exist here.
$existing = [];
$res = $conn->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "'");
while ($row = $res->fetch_assoc()) $existing[$row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']] = true;

printf("Card-identifier census — database '%s' (READ ONLY)\n", $db);
printf("Dictionary universe: %d cards\n\n", count($GLOBALS['titleData'] ?? []));

$grandUnmapped = [];   // identifier => total rows
$grandBlank = 0;
$missing = [];

printf("%-38s %8s %8s %8s %8s %8s\n", 'TABLE.COLUMN', 'DISTINCT', 'SET_NNN', 'MAPPED', 'UNMAPPED', 'BLANK');
printf("%s\n", str_repeat('-', 82));

foreach ($TARGETS as [$table, $column]) {
    if (!isset($existing[$table . '.' . $column])) { $missing[] = "$table.$column"; continue; }

    $sql = "SELECT `$column` AS v, COUNT(*) AS n FROM `$table` GROUP BY `$column`";
    $res = $conn->query($sql);
    if (!$res) { $missing[] = "$table.$column (query failed)"; continue; }

    $distinct = 0; $alreadySet = 0; $mapped = 0; $unmapped = 0; $blank = 0;
    $collisionSources = [];   // canonical SET_NNN => [source identifiers]

    while ($row = $res->fetch_assoc()) {
        $v = (string)$row['v'];
        $n = (int)$row['n'];
        $distinct++;
        if (trim($v) === '') { $blank++; $grandBlank += $n; continue; }

        if (census_is_set_nnn($v)) { $alreadySet++; $canonical = CardIDOverride($v); }
        else {
            $setNnn = CardIDLookup($v);
            if ($setNnn === null) {
                $unmapped++;
                $grandUnmapped[$v] = ($grandUnmapped[$v] ?? 0) + $n;
                continue;
            }
            $mapped++;
            $canonical = CardIDOverride($setNnn);
        }
        $collisionSources[$canonical][] = $v;
    }

    $collisions = 0;
    foreach ($collisionSources as $canonical => $sources) {
        if (count($sources) > 1) $collisions++;
    }

    printf("%-38s %8d %8d %8d %8d %8d%s\n",
        "$table.$column", $distinct, $alreadySet, $mapped, $unmapped, $blank,
        $collisions > 0 ? "   <- $collisions key(s) merge" : '');
}

echo "\n";
if ($missing) {
    echo "NOT PRESENT ON THIS BOX (expected if the table is runtime-created):\n";
    foreach ($missing as $m) echo "  - $m\n";
    echo "\n";
}

if ($grandBlank > 0) {
    printf("BLANK identifiers: %d row(s) across all tables carry an empty identifier.\n\n", $grandBlank);
}

if ($grandUnmapped) {
    arsort($grandUnmapped);
    printf("UNMAPPED identifiers: %d distinct, %d row(s) total. THIS BLOCKS CUTOVER.\n",
        count($grandUnmapped), array_sum($grandUnmapped));
    $shown = 0;
    foreach ($grandUnmapped as $id => $rows) {
        printf("  %-14s %8d row(s)\n", $id, $rows);
        if (++$shown >= $showUnmapped) {
            printf("  ... and %d more\n", count($grandUnmapped) - $shown);
            break;
        }
    }
} else {
    echo "UNMAPPED identifiers: none. Every stored identifier resolves.\n";
}

$conn->close();
