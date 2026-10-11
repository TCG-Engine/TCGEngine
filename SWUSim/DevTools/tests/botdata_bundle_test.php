<?php
// The BotData bundle's MANIFEST — the record of what a download contained, and the thing purge later
// reads — plus the purge itself. The tar streaming is exercised by hand; this is the logic.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/botdata_bundle_test.php
chdir('/var/www/html/TCGEngine');
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
require_once './SWUSim/Mod/BotDataBundle.php';
require_once './SWUSim/Mod/BotDataPurge.php';

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

// ⚠ THE FULL DOWNLOAD→PURGE ROUND TRIP, through the SAME function the HTTP endpoint calls.
// SWUBotDataBuildBundle used to build its own manifest inline while SWUBotDataBuildManifest built a
// different one, so the 'sizes' fingerprint existed only on the path the TESTS used. Production
// downloaded a manifest with no sizes, purge skipped every game as unverifiable, and then consumed the
// manifest — the owner saw "purge did nothing" with no way to retry. Two builders, one artifact.
$rt = $root . '/../botdata_rt_' . getmypid();
@mkdir($rt . '/9001', 0777, true); file_put_contents($rt . '/9001/states.jsonl', "{}\n");
@mkdir($rt . '/9002', 0777, true); file_put_contents($rt . '/9002/states.jsonl', "{}\n");
$tgzRt = SWUBotDataBuildBundle($rt);
$check(is_string($tgzRt), 'round trip: the bundle builds');
$mRt = json_decode(strval(file_get_contents(SWUBotDataManifestPath($rt))), true);
$check(is_array($mRt['sizes'] ?? null) && count($mRt['sizes']) === 2,
    'round trip: the DOWNLOAD path writes the sizes fingerprint; got ' . json_encode($mRt['sizes'] ?? null));
$rRt = SWUBotDataPurge($rt);
$check(intval($rRt['deleted'] ?? -1) === 2 && intval($rRt['skipped'] ?? -1) === 0,
    'round trip: purge after a real download DELETES the games; got ' . json_encode($rRt));
if (is_string($tgzRt)) @unlink($tgzRt);
foreach (glob($rt . '/*/*') ?: [] as $f) @unlink($f);
foreach (glob($rt . '/*', GLOB_ONLYDIR) ?: [] as $d) @rmdir($d);
@unlink(SWUBotDataManifestPath($rt)); @rmdir($rt);

// A game that appears AFTER the manifest is not in it — this is what protects an in-progress game.
$mkGame('1003');
$m2 = json_decode(strval(file_get_contents(SWUBotDataManifestPath($root))), true);
$check(!in_array('1003', $m2['games'] ?? [], true), 'a game created after the manifest is NOT in it');

// The manifest file itself must never be mistaken for a game directory.
$check(!in_array('.manifest.json', $m['games'], true), 'the manifest is not listed as a game');

// ── PURGE ────────────────────────────────────────────────────────────────────────────────────

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

// ⚠ A GAME THAT GREW SINCE THE DOWNLOAD IS NOT DELETED. The manifest names every directory present
// when the bundle was built — including a game still being PLAYED. Deleting that on purge destroys a
// live game's data mid-session, and the bundle the owner holds only has its first half anyway.
// BotDataPurge's header claimed this already worked; it did not.
// Its own root: SWUBotDataBuildManifest sweeps up EVERY directory present, so running it against the
// shared root would manifest (and then purge) the games the sections below rely on.
$grew = $root . '/../botdata_grew_' . getmypid();
@mkdir($grew . '/3001', 0777, true); file_put_contents($grew . '/3001/states.jsonl', "{}\n");
@mkdir($grew . '/3002', 0777, true); file_put_contents($grew . '/3002/states.jsonl', "{}\n");
SWUBotDataBuildManifest($grew);
file_put_contents($grew . '/3002/states.jsonl', "{}\n{}\n");   // 3002 kept recording after the download
$r4 = SWUBotDataPurge($grew);
$check(!is_dir($grew . '/3001'), 'an unchanged manifested game is still deleted');
$check(is_dir($grew . '/3002'), 'a game that RECORDED MORE since the download survives');
$check(intval($r4['skipped'] ?? -1) === 1, 'and purge reports it as skipped; got ' . json_encode($r4));
foreach (glob($grew . '/*/*') ?: [] as $f) @unlink($f);
foreach (glob($grew . '/*', GLOB_ONLYDIR) ?: [] as $d) @rmdir($d);
@unlink(SWUBotDataManifestPath($grew)); @rmdir($grew);

// ── REVIEW FOCUS 5 — purge with NO manifest at all must not wipe the corpus ──────────────────
@unlink(SWUBotDataManifestPath($root));
$r3 = SWUBotDataPurge($root);
$check(intval($r3['deleted'] ?? -1) === 0, 'purge with no manifest deletes NOTHING; got ' . json_encode($r3));
$check(is_dir($root . '/2003'), 'the corpus survives a purge that was never preceded by a download');

// A manifest naming a path OUTSIDE the root must not escape it.
@mkdir($root . '/victim', 0777, true); file_put_contents($root . '/victim/keep.txt', 'x');
file_put_contents($root . '/victim/states.jsonl', "{}\n");
// 'sizes' must be present and matching, or the fingerprint check skips the entry before the traversal
// guard is ever reached — which would make this section pass for the wrong reason.
file_put_contents(SWUBotDataManifestPath($root), json_encode(
    ['createdAt' => time(), 'games' => ['../victim', 'victim'],
     'sizes' => ['victim' => SWUBotDataFingerprint($root, 'victim')]]));
SWUBotDataPurge($root);
$check(!is_dir($root . '/victim'), 'a plain id inside the root is purged normally');
$check(is_dir($root), 'and a traversal attempt does not escape the root');

foreach (glob($root . '/*/*') ?: [] as $f) @unlink($f);
foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $d) @rmdir($d);
@unlink(SWUBotDataManifestPath($root)); @rmdir($root);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
