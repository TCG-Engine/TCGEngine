<?php
// Emits the SET_NNN migration's id-map table as plain SQL.
//
//   php AppCore/SWU/migrations/tools/materialize-id-map.php > AppCore/SWU/migrations/01_id_map.sql
//   php AppCore/SWU/migrations/tools/materialize-id-map.php --summary
//
// Run this AHEAD of the maintenance window, on the box being migrated, right after regenerating the
// card dictionaries. The point is that the migration itself is then pure SQL: it never calls PHP
// mid-run, which matters because LAMPP's CLI PHP has no mysqli and the window is not the place to
// discover that.
//
// NO DATABASE ACCESS — the map is derived entirely from the generated dictionaries, so it is
// identical whichever box builds it, and a stored value simply being ABSENT from it is the
// definition of class 3.
//
// --summary  human-readable breakdown to stderr instead of SQL to stdout. Use it to eyeball the
//            map before committing the .sql, and to confirm the token count is what you expect.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §6

$repoRoot = dirname(__DIR__, 4);

// SWUDeck's dictionary, always. The stats database lives on the swustats box and its key space is
// SWUDeck's; SWUSim's dictionary carries no UUID lookup at all, so there is nothing there to map.
$app = 'SWUDeck';
$summary = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--summary') $summary = true;
    else { fwrite(STDERR, "unknown argument: $arg\n"); exit(2); }
}

$dict = "$repoRoot/$app/GeneratedCode/GeneratedCardDictionaries.php";
if (!file_exists($dict)) {
    fwrite(STDERR, "FATAL: no generated dictionary at $dict\n"
                 . "Regenerate first: zzCardCodeGenerator.php?rootName=$app\n");
    exit(1);
}
require_once $dict;
require_once __DIR__ . '/../lib/IdentifierMap.php';

// LeaderUnitLegacyIDByCardID was renamed from LeaderUnitByUUID on 2026-08-07; accept either so this
// tool still runs against a dictionary that has not been regenerated yet. Unlike
// SWUCardIdentityLeaderUnitMap(), a miss here is FATAL and loud, which is the behaviour we want.
$leaderUnitFn = function_exists('LeaderUnitLegacyIDByCardID') ? 'LeaderUnitLegacyIDByCardID' : 'LeaderUnitByUUID';
foreach (['UUIDLookup', 'CardIDLookup', $leaderUnitFn] as $fn) {
    if (!function_exists($fn)) {
        fwrite(STDERR, "FATAL: $dict does not define $fn().\n"
                     . "That is SWUDeck's UUID translation table — without it there is no map to build.\n");
        exit(1);
    }
}

$map = SWUMigrationBuildMap();
if (!$map) { fwrite(STDERR, "FATAL: the map is empty — the dictionary did not load.\n"); exit(1); }

// ── Sanity gates. A silently-degraded map is the worst possible outcome here: the migration would
// run cleanly and drop rows the operator was told were safe. Each of these has already failed once.
$errors = [];

$leaderUnits = SWUMigrationLeaderUnitMap();
if (count($leaderUnits) === 0) {
    $errors[] = "leader-unit legacy-id map is EMPTY. Every two-sided leader's flipped-side rows "
              . "(ad86d54e97 -> TWI_017, 2,984 rows on prod) would fall to class 3 and be dropped. "
              . "This is what the 2026-08-04 SET_NNN re-key silently broke.";
}

$tokens = array_filter($map, fn($e, $k) => preg_match('/^[A-Z0-9]{2,5}_T\d{2}$/', (string)$k),
                       ARRAY_FILTER_USE_BOTH);
if (count($tokens) < 10) {
    $errors[] = sprintf(
        "only %d token id(s) in the map. Tokens are 94%% of class 3 on prod (13 ids, 54,312 rows) "
        . "and admitting them is what makes the cutover gate passable. If this is SWUDeck, the "
        . "dictionary predates the token-inclusion fix — re-run the generator with withPreview=1 "
        . "(a live re-fetch; the cached card array is token-free).", count($tokens));
}

foreach (['SOR_005' => 'a leader', '2579145458' => "that leader's UUID"] as $probe => $what) {
    if (SWUMigrationMapLookup($map, (string)$probe) === null) {
        $errors[] = "$probe ($what) is not in the map — the dictionary is not loading correctly.";
    }
}

if ($errors) {
    fwrite(STDERR, "\n=== MAP IS NOT SAFE TO USE ===\n");
    foreach ($errors as $e) fwrite(STDERR, "  * $e\n\n");
    exit(1);
}

// ── Summary mode ────────────────────────────────────────────────────────────
if ($summary) {
    $byVia = [];
    $byDisposition = [];
    foreach ($map as $entry) {
        $byVia[$entry['via']] = ($byVia[$entry['via']] ?? 0) + 1;
        $byDisposition[$entry['disposition']] = ($byDisposition[$entry['disposition']] ?? 0) + 1;
    }
    // A many-to-one fold is the reason the migration must aggregate rather than rename.
    $targets = [];
    foreach ($map as $old => $entry) {
        if ($entry['disposition'] === 'map') $targets[$entry['to']][] = $old;
    }
    $merging = array_filter($targets, fn($srcs) => count($srcs) > 1);

    fwrite(STDERR, sprintf("app          %s\n", $app));
    fwrite(STDERR, sprintf("map rows     %d\n", count($map)));
    foreach ($byDisposition as $d => $n) fwrite(STDERR, sprintf("  %-10s %d\n", $d, $n));
    fwrite(STDERR, "by rule\n");
    foreach ($byVia as $v => $n) fwrite(STDERR, sprintf("  %-20s %d\n", $v, $n));
    fwrite(STDERR, sprintf("tokens       %d\n", count($tokens)));
    fwrite(STDERR, sprintf("legacy ids %d\n", count($leaderUnits)));
    fwrite(STDERR, sprintf("\n%d target id(s) have MORE THAN ONE source — these rows MERGE, so every\n"
                         . "counter column must be SUMmed. A rename-style UPDATE would lose them.\n",
                           count($merging)));
    arsort($merging);
    $shown = 0;
    foreach ($merging as $to => $srcs) {
        fwrite(STDERR, sprintf("  %-10s <- %s\n", $to, implode(', ', $srcs)));
        if (++$shown >= 15) { fwrite(STDERR, sprintf("  ... and %d more\n", count($merging) - $shown)); break; }
    }
    exit(0);
}

// ── SQL mode ────────────────────────────────────────────────────────────────
// The collation is not incidental. It must match the identifier columns it will be JOINed against
// (all of them are utf8mb4_general_ci) or MySQL cannot use the index and the join degrades to a
// full scan per table — on 2.6M completedgame rows that is the difference between minutes and
// hours. It is also what makes the 'keep' disposition work: ci matching means the single 'green'
// row also matches 'Green' and 'GREEN', and 'keep' then writes back the ORIGINAL spelling.
$out = [];
$out[] = "-- SET_NNN identity migration — id map";
$out[] = "-- GENERATED by AppCore/SWU/migrations/tools/materialize-id-map.php. Do not hand-edit;";
$out[] = "-- regenerate after any card-dictionary change, or the map and the dictionaries disagree.";
$out[] = "--";
$out[] = "--   app          $app";
$out[] = "--   map rows     " . count($map);
$out[] = "--   tokens       " . count($tokens);
$out[] = "--   legacy ids " . count($leaderUnits);
$out[] = "--";
$out[] = "-- disposition 'map'  -> write newID";
$out[] = "-- disposition 'keep' -> write the ORIGINAL stored value (class 2: base colours, sentinels)";
$out[] = "-- absent from this table -> class 3; the migration's INNER JOIN drops the row.";
$out[] = "";
$out[] = "DROP TABLE IF EXISTS `swu_id_map`;";
$out[] = "CREATE TABLE `swu_id_map` (";
$out[] = "  `oldID` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,";
$out[] = "  `newID` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,";
$out[] = "  `disposition` enum('map','keep') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'map',";
$out[] = "  `via` varchar(24) COLLATE utf8mb4_general_ci NOT NULL,";
$out[] = "  PRIMARY KEY (`oldID`)";
$out[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$out[] = "";

$q = fn($s) => "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$s) . "'";

$rows = [];
foreach ($map as $old => $entry) {
    $rows[] = sprintf('(%s,%s,%s,%s)',
        $q($old), $q($entry['to']), $q($entry['disposition']), $q($entry['via']));
}
// Chunked so the file stays under any sane max_allowed_packet without needing one giant statement.
foreach (array_chunk($rows, 250) as $chunk) {
    $out[] = "INSERT INTO `swu_id_map` (`oldID`,`newID`,`disposition`,`via`) VALUES";
    $out[] = "  " . implode(",\n  ", $chunk) . ";";
}
$out[] = "";

// A PK violation on insert would already have failed loudly above; this catches the subtler case of
// a map that loaded but is missing whole families.
$out[] = "-- Fail loudly rather than migrate against a truncated map.";
$out[] = "SELECT IF(COUNT(*) = " . count($map) . ", 'id map OK',";
$out[] = "  CONCAT('FATAL: swu_id_map has ', COUNT(*), ' rows, expected " . count($map) . "'))";
$out[] = "  AS id_map_check FROM `swu_id_map`;";
$out[] = "";

echo implode("\n", $out);
