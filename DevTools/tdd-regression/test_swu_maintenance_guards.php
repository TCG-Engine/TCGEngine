<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swu_maintenance_guards.php
//
// Every web-reachable path that writes a SET_NNN-migration target table must sit behind
// SWUMaintenanceRequire(), or maintenance mode is a sign on a door that does not lock.
//
// This RE-DERIVES the writer list from the source on every run rather than checking a hardcoded
// list, so a writer added next month is caught the first time this runs — that is the whole point.
// A new writer either gets a gate or gets added to ALLOWED below with a reason.
//
// Scope is the swustats box's `swudeck` database. SWUSim writes its OWN `swusim` database on
// petranaki (verified: separate MySQL servers, separate schemas), so its writers are out of scope
// for this migration — they would matter only for a future swusim re-key.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §9
header('Content-Type: text/plain');
require_once __DIR__ . '/../../AppCore/SWU/Maintenance.php';

$root = dirname(__DIR__, 2);

// The tables the migration rebuilds and RENAMEs away. A write to any of these between the build
// and the swap is silently discarded.
$TARGETS = ['carddeckstats','cardmetastats','deckstats','opponentdeckstats','opponentnamedbasestats',
            'deckmetastats','deckmetamatchupstats','completedgame','favoritedeck','meleetournamentdeck'];

// Not web-reachable, so they cannot fire during the window. Each needs a REASON, not just an entry.
$ALLOWED = [
    'Database/functions.inc.php'      => 'library only; its favoritedeck writers are reached solely from SWUSim/ (petranaki, separate database)',
    'Utils/ConvertBasesToCanonical.php' => 'one-shot utility, no web entry point; only referenced by its own regression test',
    'Stats/MeleeTournamentParser.php' => 'library; both of its web entry points (FindOrImportMeleeTournament, MeleeTournamentParserAPI) are gated',
];

$checks = [];
$writeRe = '/(INSERT\s+(INTO|IGNORE)|REPLACE\s+INTO|UPDATE|DELETE\s+FROM|TRUNCATE(\s+TABLE)?)\s+`?('
         . implode('|', $TARGETS) . ')`?\b/i';

// Walk the tree, skipping anything that cannot be hit by a browser.
$skipDir = '#/(DevTools|Tests|node_modules|vendor|SWUSim|SoulMasters|Azuki|GrandArchive|Gudnak|Hellbreak|Lorcana|RBDeck|MatchFlowTestSim|MatchTestSim)(/|$)#i';
$writers = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->getExtension() !== 'php') continue;
    $abs = $file->getPathname();
    $rel = ltrim(str_replace($root, '', $abs), '/');
    if (preg_match($skipDir, '/' . $rel)) continue;
    if (strpos($rel, 'zzRegression') === 0 || strpos($rel, 'zzRun') === 0) continue;  // test runners

    $src = file_get_contents($abs);
    if (!preg_match($writeRe, $src)) continue;
    $writers[$rel] = strpos($src, 'SWUMaintenanceRequire') !== false;
}

$checks['found some writers at all'] = count($writers) > 0;   // a broken regex would pass everything

$ungated = [];
foreach ($writers as $rel => $gated) {
    if ($gated) continue;
    if (isset($ALLOWED[$rel])) continue;
    $ungated[] = $rel;
}
$checks['every web-reachable writer is gated'] = count($ungated) === 0;

// ── Deck GAMESTATE FILE writers ─────────────────────────────────────────────
// The 'full' level exists for the deck-file rewrite, and those writers touch the filesystem, not
// SQL — so the query regex above cannot see them. Derive them separately, or a new deck-writing
// endpoint would sail past this test while the rewrite runs underneath it.
// ⚠ MATCH A CALL, NOT A SUBSTRING. A bare `WriteGamestate` also matches inside longer identifiers:
// Core/GameAuth.php's SimGameWriteGamestateCache() writes to APCu, never to a deck file, and was
// reported as an ungated deck writer for that reason alone. The lookbehind requires a non-identifier
// character before the name, and `\s*\(` requires it to be an actual call.
$fileWriteRe = '/((?<![A-Za-z0-9_])WriteGamestate\s*\(|file_put_contents\s*\([^;]*Games\/)/i';
$fileWriters = [];
$it2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it2 as $file) {
    if ($file->getExtension() !== 'php') continue;
    $abs = $file->getPathname();
    $rel = ltrim(str_replace($root, '', $abs), '/');
    if (preg_match($skipDir, '/' . $rel)) continue;
    // APIs/ and SWUDeck/ are the obvious endpoints; Core/ is where the shared engine chokepoints
    // live (EngineExecuteLoadedAction, the replay reset) and is where the first sweep missed them.
    if (strpos($rel, 'APIs/') !== 0 && strpos($rel, 'SWUDeck/') !== 0 && strpos($rel, 'Core/') !== 0) continue;
    $src = file_get_contents($abs);
    if (!preg_match($fileWriteRe, $src)) continue;
    $fileWriters[$rel] = strpos($src, 'SWUMaintenanceRequire') !== false;
}

// Exemptions need a reason, same rule as above.
$ALLOWED_FILE = [
    // Not an endpoint — this is where WriteGamestate() is DEFINED, and it is a generated,
    // gitignored file (zzGameCodeGenerator), so a gate added here would be wiped on the next
    // regen. Its callers are what get gated.
    'SWUDeck/GamestateParser.php' => 'generated library; defines WriteGamestate(), not reachable as a page',
    'Core/RegressionTestFramework.php' => 'test harness; not reachable from the web on a prod box',
];
$ungatedFiles = [];
foreach ($fileWriters as $rel => $gated) {
    if (!$gated && !isset($ALLOWED_FILE[$rel])) $ungatedFiles[] = $rel;
}
$checks['every deck-file writer is gated'] = count($ungatedFiles) === 0;

// An ALLOWED entry that has since GAINED a gate, or that no longer writes at all, is stale — the
// exemption list must not quietly accumulate.
$staleAllows = [];
foreach ($ALLOWED as $rel => $why) {
    if (!isset($writers[$rel])) $staleAllows[] = "$rel (no longer writes a target table)";
}
foreach ($ALLOWED_FILE as $rel => $why) {
    if (!isset($fileWriters[$rel])) $staleAllows[] = "$rel (no longer writes a deck file)";
}
$checks['no stale entries in the allow-list'] = count($staleAllows) === 0;

// The specific files this migration's window depends on. Named explicitly so that a refactor which
// drops a gate fails loudly here even if the file also stops matching the regex above.
$MUST_BE_GATED = [
    'APIs/SubmitGameResult.php', 'APIs/SubmitManualGameResult.php', 'SWUDeck/ClearStats.php',
    'APIs/FindOrImportMeleeTournament.php', 'Stats/MeleeTournamentParserAPI.php',
    'APIs/EditDeckCard.php', 'SWUDeck/CreateDeck.php', 'SWUDeck/Initialize.php',
    'SWUDeck/RefreshImport.php', 'SWUDeck/DeleteVersion.php', 'APIs/MatchReplay.php',
    'zzModPage.php', 'zzSWUDeckMatrix.php', 'zzMigrateOwnerStatsToCommunity.php',
];
$missing = [];
foreach ($MUST_BE_GATED as $rel) {
    $p = "$root/$rel";
    if (!is_file($p)) { $missing[] = "$rel (FILE MISSING)"; continue; }
    if (strpos(file_get_contents($p), 'SWUMaintenanceRequire') === false) $missing[] = $rel;
}
$checks['every named critical writer is gated'] = count($missing) === 0;

// ── Per-ACTION gating, where a page has several independent write paths ─────
// Presence-in-file is a weak check. zzModPage.php has a 'stats' gate on its truncate action and
// passed this test for a while with its deck-fill action — which calls WriteGamestate() directly —
// completely ungated. Assert the specific handler, not just the file.
$modPage = file_get_contents("$root/zzModPage.php");
if (preg_match('/fillSWUDeckGame.*?\n\}/s', $modPage, $m)) {
    $checks["zzModPage's deck-fill action is gated"] = strpos($m[0], 'SWUMaintenanceRequire') !== false;
} else {
    $checks["zzModPage's deck-fill action is gated"] = 'fill handler not found — did it move?';
}
$checks["zzModPage gates BOTH of its write actions"] = substr_count($modPage, 'SWUMaintenanceRequire') >= 2;

// The toggle page must never gate itself: turning maintenance OFF cannot require it to be off.
$toggle = file_get_contents("$root/zzMaintenanceMode.php");
$checks['the toggle page does not gate itself'] = strpos($toggle, 'SWUMaintenanceRequire') === false;

// Levels behave as advertised. 'stats' must NOT block deck saves, or the DB migration would
// needlessly take the deckbuilder down with it.
$R = 'SWU_MAINT_SELFTEST';
@mkdir("$root/$R");
SWUMaintenanceSet($R, 'stats', 'selftest', 'test');
$checks["level 'stats' blocks stats writes"]   = SWUMaintenanceBlocks($R, 'stats') === true;
$checks["level 'stats' allows deck saves"]     = SWUMaintenanceBlocks($R, 'deck')  === false;
SWUMaintenanceSet($R, 'full', 'selftest', 'test');
$checks["level 'full' blocks deck saves"]      = SWUMaintenanceBlocks($R, 'deck')  === true;
SWUMaintenanceSet($R, 'off', '', 'test');
$checks["level 'off' blocks nothing"]          = SWUMaintenanceBlocks($R, 'stats') === false;
@rmdir("$root/$R");

echo "sql writers:       " . count($writers) . " (" . count(array_filter($writers))
   . " gated, " . count($ALLOWED) . " exempt)\n";
echo "deck-file writers: " . count($fileWriters) . " (" . count(array_filter($fileWriters)) . " gated)\n";
if ($ungated)      echo "UNGATED (sql):\n  " . implode("\n  ", $ungated) . "\n";
if ($ungatedFiles) echo "UNGATED (deck files):\n  " . implode("\n  ", $ungatedFiles) . "\n";
if ($missing)     echo "LOST A GATE:\n  " . implode("\n  ", $missing) . "\n";
if ($staleAllows) echo "STALE ALLOW-LIST:\n  " . implode("\n  ", $staleAllows) . "\n";

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
