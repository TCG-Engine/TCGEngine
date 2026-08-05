<?php
// Card-identifier census — READ ONLY. Runs only SELECTs; writes nothing, changes nothing.
//
// Must be a web page, not a CLI script: LAMPP's CLI binary has no mysqli, so GetLocalMySQLConnection()
// fatals outside the Apache SAPI.
//
//   https://<host>/TCGEngine/zzCardIdentifierCensus.php
//   https://<host>/TCGEngine/zzCardIdentifierCensus.php?showUnmapped=100
//
// Answers the questions that gate the SET_NNN identity migration
// (docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md):
//   1. Which stored identifiers fall into class 3 — unresolvable?  (a non-empty class 3 blocks cutover)
//   2. How many rows collapse onto a shared key once reprints fold?  (the aggregation delta)
//   3. Which tables/columns actually exist on THIS box?  (prod has drifted from database.sql)
//
// Classification mirrors the spec:
//   class 1  already SET_NNN            -> passes through
//   class 2  known non-card value       -> preserved verbatim (base colours, sentinels)
//   class 3  unresolvable               -> dropped + logged; ANY of these blocks cutover

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require_once __DIR__ . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/AppCore/SWU/Overrides.php';
// The classifier is SHARED with tools/materialize-id-map.php, which builds the map the migration
// actually joins against. If this page said a value was mappable and the map omitted it, the
// migration would silently drop rows the operator had been told were safe. One implementation.
require_once __DIR__ . '/AppCore/SWU/migrations/lib/IdentifierMap.php';
require_once __DIR__ . '/Database/ConnectionManager.php';
require_once __DIR__ . '/AccountFiles/AccountDatabaseAPI.php';
require_once __DIR__ . '/AccountFiles/AccountSessionAPI.php';

$error = CheckLoggedInUserMod();
if ($error !== "") { http_response_code(403); echo "Forbidden: " . htmlspecialchars($error); exit(); }

$showUnmapped = isset($_GET['showUnmapped']) ? max(1, (int)$_GET['showUnmapped']) : 25;

// (table, column) pairs holding a card identifier. Verified against information_schema below —
// anything absent on this box is reported as missing rather than fataling.
// 'poly' marks a column that legitimately holds a bucket key (card id OR base colour).
$TARGETS = SWUMigrationTargets();

$conn = GetLocalMySQLConnection();
$db = $conn->query('SELECT DATABASE()')->fetch_row()[0];

$existing = [];
$res = $conn->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "'");
while ($row = $res->fetch_assoc()) $existing[$row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']] = true;

$leaderUnits = SWUMigrationLeaderUnitMap();

$class3 = [];        // identifier => total rows
$leaderUnitHits = [];// asset hash => ['card' => CardID, 'rows' => n]
$blankRows = 0;
$missing = [];
$totalMerges = 0;

header('Content-Type: text/html; charset=utf-8');
echo "<!doctype html><meta charset='utf-8'><title>Card identifier census</title>";
echo "<style>body{font:13px/1.5 ui-monospace,Menlo,monospace;padding:24px;max-width:1100px}"
   . "pre{white-space:pre-wrap}h2{font-size:15px;margin:22px 0 6px}.bad{color:#b00}.ok{color:#070}</style>";
echo "<h1 style='font-size:17px'>Card-identifier census</h1>";
printf("<p>Database <b>%s</b> &mdash; READ ONLY. Dictionary universe: <b>%d</b> cards, "
     . "<b>%d</b> leader-unit asset ids.</p>",
    htmlspecialchars($db), count($GLOBALS['titleData'] ?? []), count($leaderUnits));

echo "<pre>";
printf("%-38s %8s %8s %8s %8s %8s %8s\n",
    'TABLE.COLUMN', 'DISTINCT', 'CLASS1', 'MAPPED', 'CLASS2', 'CLASS3', 'BLANK');
echo str_repeat('-', 92) . "\n";

foreach ($TARGETS as [$table, $column, $poly]) {
    if (!isset($existing[$table . '.' . $column])) { $missing[] = "$table.$column"; continue; }

    $res = $conn->query("SELECT `$column` AS v, COUNT(*) AS n FROM `$table` GROUP BY `$column`");
    if (!$res) { $missing[] = "$table.$column (query failed)"; continue; }

    $distinct = 0; $c1 = 0; $mapped = 0; $c2 = 0; $c3 = 0; $blank = 0;
    $canonicalSources = [];

    while ($row = $res->fetch_assoc()) {
        $v = (string)$row['v'];
        $n = (int)$row['n'];
        $distinct++;

        // Counted per (table,column): a completedgame row with BOTH heroes blank contributes to
        // each column, so this total is per-column occurrences, not distinct rows. Per-table row
        // counts need their own query — see the spec's blank breakdown.
        if (trim($v) === '') { $blank++; $blankRows += $n; continue; }

        $r = SWUMigrationClassify($v, $poly);

        if ($r['class'] === 2) { $c2++; continue; }

        if ($r['class'] === 3) {
            // Blocks cutover.
            $c3++;
            $class3[$v] = ($class3[$v] ?? 0) + $n;
            continue;
        }

        // Class 1 — resolves. 'via' distinguishes "was already SET_NNN" from "we translated it",
        // which is what the CLASS1/MAPPED columns report.
        if ($r['via'] === 'set-nnn') $c1++; else $mapped++;
        $canonicalSources[$r['to']][] = $v;

        if ($r['via'] === 'leader-unit-asset') {
            $leaderUnitHits[$v] = ['card' => $r['to'], 'rows' => ($leaderUnitHits[$v]['rows'] ?? 0) + $n];
        }
    }

    $merges = 0;
    foreach ($canonicalSources as $sources) if (count($sources) > 1) $merges++;
    $totalMerges += $merges;

    printf("%-38s %8d %8d %8d %8d %8d %8d%s\n",
        "$table.$column", $distinct, $c1, $mapped, $c2, $c3, $blank,
        $merges > 0 ? "   <- $merges key(s) merge" : '');
}
echo "</pre>";

if ($missing) {
    echo "<h2>Not present on this box</h2><pre>";
    foreach ($missing as $m) echo "  - " . htmlspecialchars($m) . "\n";
    echo "</pre><p>Expected for runtime-created tables; anything else is schema drift worth checking.</p>";
}

echo "<h2>Aggregation</h2><pre>";
printf("%d key(s) across all columns have more than one source identifier.\n", $totalMerges);
echo "Those rows MERGE — counters must be SUMmed, not overwritten.\n</pre>";

if ($leaderUnitHits) {
    echo "<h2>Leader-unit asset ids used as card identifiers</h2><pre>";
    foreach ($leaderUnitHits as $asset => $info) {
        printf("  %-14s -> %-10s %8d row(s)\n", $asset, $info['card'], $info['rows']);
    }
    echo "\nThese merge into the owning card, fixing a split-identity stats bug.\n</pre>";
}

if ($blankRows > 0) {
    printf("<h2>Blank identifiers</h2><pre>%d per-column occurrence(s) of an empty identifier — class 3.\n"
         . "NOTE: per-COLUMN, not per-row. A completedgame row with both heroes blank counts twice.\n</pre>",
        $blankRows);
}

echo "<h2>Class 3 — unresolvable</h2><pre>";
if ($class3) {
    arsort($class3);
    printf("<span class='bad'>%d distinct, %d row(s). THIS BLOCKS CUTOVER.</span>\n\n",
        count($class3), array_sum($class3));
    $shown = 0;
    foreach ($class3 as $id => $rows) {
        printf("  %-14s %8d row(s)\n", htmlspecialchars($id), $rows);
        if (++$shown >= $showUnmapped) {
            printf("  ... and %d more (append ?showUnmapped=%d)\n", count($class3) - $shown, count($class3));
            break;
        }
    }
} else {
    echo "<span class='ok'>None. Every stored identifier resolves or is known-legitimate.</span>\n";
}
echo "</pre>";

$conn->close();
