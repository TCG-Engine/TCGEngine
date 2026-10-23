<?php
// docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_local_bug_report_import.php
//
// LocalBugReportImport puts a Petranaki bug-report package (bugreport-P-<n>-game-<g>.zip, downloaded from the viewer)
// into a local SWUSim/Games/<g>/ under the SAME game number, so the report text and the local game agree
// (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md). Local game numbers are in the 46,000s
// and prod's start low, so collisions are certain: an existing folder is moved aside to <g>.bak-<YmdHis>, never
// overwritten. A zip with any other entry, or a path, is refused before anything is written.
// CLI only; temp games dir.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', '1');
chdir('/var/www/html/TCGEngine');
require_once './LocalBugReportLib.php';

$root = sys_get_temp_dir() . '/lbr-import-' . getmypid() . '-' . time();
$games = "$root/Games";
mkdir($games, 0775, true);
file_put_contents("$games/GameIDCounter.txt", '46300');
$now = 1790000000;

function make_zip(string $path, array $entries): string {
    $z = new ZipArchive();
    $z->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $content) $z->addFromString($name, $content);
    $z->close();
    return $path;
}
$report = fn(string $game) => json_encode(['report_number' => 'P-42', 'number' => 42, 'game_name' => $game, 'verified' => true, 'has_snapshot' => true]);
$tree = function (string $dir): array {
    $out = [];
    foreach (glob("$dir/*") ?: [] as $f) $out[basename($f)] = is_dir($f) ? 'dir' : file_get_contents($f);
    ksort($out);
    return $out;
};
$checks = [];

// ── a game number this machine has never used ─────────────────────────────────────────────────────
$r = LocalBugReportImport(make_zip("$root/a.zip", ['report.json' => $report('512'), 'Gamestate.txt' => "GS-512\n"]), $games, $now);
$checks['import: fresh game → files written, backup null'] = $r['ok'] === true && $r['gameName'] === '512' && $r['backup'] === null
    && file_get_contents("$games/512/Gamestate.txt") === "GS-512\n" && is_file("$games/512/report.json");

// ── the number is taken locally ───────────────────────────────────────────────────────────────────
mkdir("$games/777");
file_put_contents("$games/777/Gamestate.txt", "LOCAL GAME\n");
file_put_contents("$games/777/Rematch.json", '{}');
$r = LocalBugReportImport(make_zip("$root/b.zip", ['report.json' => $report('777'), 'Gamestate.txt' => "GS-777\n"]), $games, $now);
$bak = "$games/777.bak-" . date('YmdHis', $now);
$checks['import: existing folder backed up'] = $r['ok'] === true && $r['backup'] === $bak && is_dir($bak)
    && file_get_contents("$bak/Gamestate.txt") === "LOCAL GAME\n" && is_file("$bak/Rematch.json")
    && file_get_contents("$games/777/Gamestate.txt") === "GS-777\n" && !is_file("$games/777/Rematch.json");

// ── hostile or broken packages: refused, nothing touched ──────────────────────────────────────────
mkdir("$games/888");
file_put_contents("$games/888/Gamestate.txt", "KEEP ME\n");
$before = $tree($games);
$r = LocalBugReportImport(make_zip("$root/c.zip", ['report.json' => $report('888'), 'Gamestate.txt' => 'x', '../escape.txt' => 'pwned']), $games, $now);
$checks['import: path traversal entry refused'] = $r['ok'] === false && $tree($games) === $before && !is_file("$root/escape.txt")
    && !is_file("$games/escape.txt");
$r = LocalBugReportImport(make_zip("$root/d.zip", ['report.json' => $report('888'), 'Gamestate.txt' => 'x', 'Rematch.json' => '{}']), $games, $now);
$checks['import: extra entry refused'] = $r['ok'] === false && $tree($games) === $before;
$r = LocalBugReportImport(make_zip("$root/e.zip", ['report.json' => $report('../888'), 'Gamestate.txt' => 'x']), $games, $now);
$checks['import: non-numeric game_name refused'] = $r['ok'] === false && $tree($games) === $before;
$r = LocalBugReportImport(make_zip("$root/f.zip", ['report.json' => $report('888')]), $games, $now);
$checks['import: no Gamestate.txt (unverified) refused with a reason'] = $r['ok'] === false && stripos((string)$r['error'], 'snapshot') !== false
    && $tree($games) === $before;
file_put_contents("$root/g.zip", 'not a zip');
$r = LocalBugReportImport("$root/g.zip", $games, $now);
$checks['import: not a zip refused'] = $r['ok'] === false && $tree($games) === $before;
$checks['import: GameIDCounter.txt untouched'] = file_get_contents("$games/GameIDCounter.txt") === '46300';

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
rmdir($root);

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
exit(empty($fails) ? 0 : 1);
