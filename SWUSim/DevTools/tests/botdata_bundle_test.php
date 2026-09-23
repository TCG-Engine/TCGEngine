<?php
// The BotData bundle's MANIFEST — the record of what a download contained, and the thing purge later
// reads — plus the purge itself. The tar streaming is exercised by hand; this is the logic.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/botdata_bundle_test.php
chdir('/var/www/html/TCGEngine');
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
require_once './SWUSim/Mod/BotDataBundle.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$root = sys_get_temp_dir() . '/botdata_test_' . getmypid();
$mkGame = function (string $id) use ($root) {
    @mkdir($root . '/' . $id, 0777, true);
    file_put_contents($root . '/' . $id . '/states.jsonl', "{}\n");
};
$mkGame('1001'); $mkGame('1002');

$m = SWUBotDataBuildManifest($root);
$check(is_array($m['games'] ?? null) && count($m['games']) === 2, 'the manifest lists both games; got ' . json_encode($m['games'] ?? null));
$check(in_array('1001', $m['games'], true) && in_array('1002', $m['games'], true), 'by game id');
$check(($m['createdAt'] ?? 0) > 0, 'with a timestamp');
$check(is_file(SWUBotDataManifestPath($root)), 'and it is persisted for purge to read');

// ⚠ THE MANIFEST ARMS PURGE, so it must only exist once the bundle has actually been BUILT (review
// finding #2). The first version wrote it before streaming tar, so an aborted or failed download left
// purge armed for 100 games the owner never received — and BotData is gitignored with no other copy.
@unlink(SWUBotDataManifestPath($root));
$bad = SWUBotDataBuildBundle($root . '/definitely-not-here');
$check($bad === null, 'a bundle build that FAILS returns null; got ' . json_encode($bad));
$check(!is_file(SWUBotDataManifestPath($root)), 'and arms nothing');
$tgz = SWUBotDataBuildBundle($root);
$check(is_string($tgz) && is_file($tgz) && filesize($tgz) > 0, 'a successful build returns a real tarball');
$check(is_file(SWUBotDataManifestPath($root)), 'and only THEN is the manifest written');
if (is_string($tgz)) {
    $listed = shell_exec('tar -tzf ' . escapeshellarg($tgz) . ' 2>/dev/null');
    $check(strpos(strval($listed), '.manifest.json') !== false, 'the manifest ships inside the bundle');
    $check(strpos(strval($listed), '1001/') !== false, 'along with the games');
    @unlink($tgz);
}

// A game that appears AFTER the manifest is not in it — this is what protects an in-progress game.
$mkGame('1003');
$m2 = json_decode(strval(file_get_contents(SWUBotDataManifestPath($root))), true);
$check(!in_array('1003', $m2['games'] ?? [], true), 'a game created after the manifest is NOT in it');

// The manifest file itself must never be mistaken for a game directory.
$check(!in_array('.manifest.json', $m['games'], true), 'the manifest is not listed as a game');

// ── PURGE ────────────────────────────────────────────────────────────────────────────────────
require_once './SWUSim/Mod/BotDataPurge.php';

$mkGame('2001'); $mkGame('2002');
SWUBotDataBuildManifest($root);              // manifest now covers 1001,1002,1003,2001,2002
$mkGame('2003');                             // created AFTER the download
$r = SWUBotDataPurge($root);
$check(!is_dir($root . '/2001'), 'a manifested game is deleted');
$check(is_dir($root . '/2003'), 'a game created AFTER the download SURVIVES');
$check(intval($r['kept'] ?? -1) === 1, 'and is reported as kept; got ' . json_encode($r));

// Purge twice is a no-op the second time.
$r2 = SWUBotDataPurge($root);
$check(intval($r2['deleted'] ?? -1) === 0, 'purging twice deletes nothing more; got ' . json_encode($r2));
$check(is_dir($root . '/2003'), 'and still does not touch the unmanifested game');

// ── REVIEW FOCUS 5 — purge with NO manifest at all must not wipe the corpus ──────────────────
@unlink(SWUBotDataManifestPath($root));
$r3 = SWUBotDataPurge($root);
$check(intval($r3['deleted'] ?? -1) === 0, 'purge with no manifest deletes NOTHING; got ' . json_encode($r3));
$check(is_dir($root . '/2003'), 'the corpus survives a purge that was never preceded by a download');

// A manifest naming a path OUTSIDE the root must not escape it.
@mkdir($root . '/victim', 0777, true); file_put_contents($root . '/victim/keep.txt', 'x');
file_put_contents(SWUBotDataManifestPath($root), json_encode(['createdAt' => time(), 'games' => ['../victim', 'victim']]));
SWUBotDataPurge($root);
$check(!is_dir($root . '/victim'), 'a plain id inside the root is purged normally');
$check(is_dir($root), 'and a traversal attempt does not escape the root');

foreach (glob($root . '/*/*') ?: [] as $f) @unlink($f);
foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $d) @rmdir($d);
@unlink(SWUBotDataManifestPath($root)); @rmdir($root);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
