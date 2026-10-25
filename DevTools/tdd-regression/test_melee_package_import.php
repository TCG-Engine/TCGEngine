<?php
// docker exec otmtcge-swudeck-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_melee_package_import.php
//
// Tournament packages (2026-10-22): melee.gg 403s swustats.net, so a tournament is recorded locally into a
// package (DevTools/melee-package.php) and swustats.net imports it by REPLAY (Stats/MeleePackage.php).
// Fixture: fixtures/melee-437850.json.gz, recorded from melee 2026-10-22 (34 decks, 209 matchups — the
// same counts the live importer wrote to the local database).
// CLI only: it imports into the scratch database <site db>_meleepkg (recreated here), never the site's own.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

chdir(__DIR__ . '/../../Stats');
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Stats/MeleePackage.php';

$sourceDb = ResolveDatabaseName();
$scratchDb = $sourceDb . '_meleepkg';
function reset_scratch(string $sourceDb, string $scratchDb): void {
    putenv("MYSQL_DATABASE_NAME=$sourceDb");
    $admin = GetLocalMySQLConnection();
    $admin->query("CREATE DATABASE IF NOT EXISTS `$scratchDb`");
    foreach (['meleetournament', 'meleetournamentdeck', 'meleetournamentmatchup'] as $t) {
        $admin->query("DROP TABLE IF EXISTS `$scratchDb`.`$t`");
        $admin->query("CREATE TABLE `$scratchDb`.`$t` LIKE `$sourceDb`.`$t`");
    }
    $admin->close();
    putenv("MYSQL_DATABASE_NAME=$scratchDb");
}
reset_scratch($sourceDb, $scratchDb);
require_once __DIR__ . '/../../Stats/MeleeTournamentParserAPI.php';
ini_set('error_log', '/dev/null'); // the card-id lookup logs one line per leader

$checks = [];
$raw = file_get_contents(__DIR__ . '/fixtures/melee-437850.json.gz');
$gz = fn(array $p) => gzencode(json_encode($p));

// ── decode ────────────────────────────────────────────────────────────────────────────────────────
$pkg = SWUMeleePackageDecode($raw, $err);
$checks['decode: the fixture is a valid package'] = is_array($pkg) && $err === null;
$checks['decode: carries the melee id and the recorded summary'] = ($pkg['melee_tournament_id'] ?? null) === '437850'
    && ($pkg['summary']['decks'] ?? null) === 34 && ($pkg['summary']['matchups'] ?? null) === 209;
$checks['decode: rejects a file that is not gzip'] = SWUMeleePackageDecode('{"format":"x"}', $e) === null && $e !== null;
$checks['decode: rejects an unknown format'] = SWUMeleePackageDecode($gz(['format' => 'other'] + $pkg), $e) === null && $e !== null;
$checks['decode: rejects a missing/non-numeric melee id'] = SWUMeleePackageDecode($gz(['melee_tournament_id' => '1 OR 1'] + $pkg), $e) === null;
$checks['decode: rejects a package with no answers'] = SWUMeleePackageDecode($gz(['responses' => []] + $pkg), $e) === null;
$foreign = $pkg; $foreign['responses'][0]['url'] = 'http://169.254.169.254/latest/meta-data';
$checks['decode: rejects an answer that is not from https://melee.gg/'] = SWUMeleePackageDecode($gz($foreign), $e) === null;
$checks['decode: rejects a file over the size cap'] = SWUMeleePackageDecode(str_repeat('x', SWU_MELEE_PACKAGE_MAX_BYTES + 1), $e) === null;

// ── import by replay into an empty database ───────────────────────────────────────────────────────
$conn = GetLocalMySQLConnection();
$r = SWUMeleePackageImport($conn, $pkg);
$checks['import: status imported'] = $r['status'] === 'imported';
$checks['import: decks/matchups equal the recorded summary'] = ($r['summary']['decks'] ?? null) === 34 && ($r['summary']['matchups'] ?? null) === 209;
$checks['import: no mismatch warning'] = !isset($r['warning']);
$checks['import: the tournament is named'] = ($r['summary']['tournament_name'] ?? '') !== '';
$checks['import: the fetch layer is back to live afterwards'] = SWUMeleeFetchMode() === 'live';
$tid = $r['tournament_id'];

// ── the same package again: nothing is duplicated ─────────────────────────────────────────────────
$again = SWUMeleePackageImport($conn, $pkg);
$checks['re-import: already_imported, same tournament'] = $again['status'] === 'already_imported' && $again['tournament_id'] === $tid;
$checks['re-import: no duplicate decks'] = SWUMeleeTournamentSummary($conn, $tid)['decks'] === 34;
$tournaments = (int)$conn->query('SELECT COUNT(*) FROM meleetournament')->fetch_row()[0];
$checks['re-import: no second tournament row'] = $tournaments === 1;

// ── the empty shell of a crashed import is replaced, not reported as done ─────────────────────────
$conn->query("DELETE FROM meleetournamentdeck WHERE tournamentID = $tid");
$shell = SWUMeleePackageImport($conn, $pkg);
$checks['empty shell: re-imported'] = $shell['status'] === 'imported' && ($shell['summary']['decks'] ?? null) === 34;

// ── a package missing an answer fails loudly — proof that replay never falls back to melee ────────
reset_scratch($sourceDb, $scratchDb);
$conn = GetLocalMySQLConnection();
$holed = $pkg;
foreach ($holed['responses'] as $i => $resp) {
    if (strpos($resp['url'], '/Decklist/GetTournamentViewData/') !== false) { unset($holed['responses'][$i]); break; }
}
$holed['responses'] = array_values($holed['responses']);
$h = SWUMeleePackageImport($conn, $holed);
$checks['missing answer: the import fails'] = $h['status'] === 'failed';
$checks['missing answer: says the package lacks an answer'] = stripos($h['message'], 'no recorded answer') !== false;
$checks['missing answer: the fetch layer is back to live'] = SWUMeleeFetchMode() === 'live';

// ── a replay that disagrees with the recorded summary is flagged ──────────────────────────────────
reset_scratch($sourceDb, $scratchDb);
$conn = GetLocalMySQLConnection();
$lying = $pkg; $lying['summary']['decks'] = 99;
$l = SWUMeleePackageImport($conn, $lying);
$checks['mismatch: still imported, with a warning'] = $l['status'] === 'imported' && isset($l['warning']);
$conn->close();

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
exit(empty($fails) ? 0 : 1);
