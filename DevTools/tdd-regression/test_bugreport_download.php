<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_bugreport_download.php
//
// zzBugReportViewer.php's "Download package" (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md):
// a Petranaki report's package holds BOTH players' hidden cards, so it is served only through the viewer, only to
// CheckLoggedInUserModStrict() (which ignores DEVENV), as bugreport-P-<n>-game-<g>.zip = report.json + Gamestate.txt.
// A loopback request is strict-mod; a request with a non-loopback Host is a stranger. Saves its own reports through
// LocalBugReportSave and deletes them at the end (one through the viewer's own Delete).
header('Content-Type: text/plain');
chdir(__DIR__ . '/../..');
require_once './Database/ConnectionManager.php';
require_once './LocalBugReportLib.php';

function viewer(string $query, array $post = null, bool $asMod = true, ?string $origin = 'http://localhost'): array {
    $ch = curl_init('http://localhost/TCGEngine/zzBugReportViewer.php' . $query);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HEADER => true];
    if ($post !== null) { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = http_build_query($post); }
    $headers = $asMod ? [] : ['Host: petranaki.invalid'];
    if ($origin !== null) $headers[] = 'Origin: ' . $origin;
    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);
    $raw = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$code, substr($raw, 0, $hsize), substr($raw, $hsize)];
}

$conn = GetLocalMySQLConnection();
$now = time();
$base = ['gameName' => '424242', 'playerID' => '1', 'reporter' => 'dltest', 'accountId' => null, 'accountName' => null,
    'description' => 'download test', 'clientIp' => '192.0.2.10', 'createdAt' => $now];
$snap = "download-test gamestate\nline two\n";
$v = LocalBugReportSave($base + ['verified' => true, 'unverifiedReason' => '', 'gamestateHash' => hash('sha256', $snap)], $snap, 'dl-' . bin2hex(random_bytes(8)), $conn);
$u = LocalBugReportSave($base + ['verified' => false, 'unverifiedReason' => 'game not found', 'gamestateHash' => ''], null, 'dl-' . bin2hex(random_bytes(8)), $conn);
$nv = $v['reportNumber']; $nu = $u['reportNumber'];
$checks = [];
$checks['precondition: two reports saved'] = $v['ok'] && $u['ok'];

[$code, $head, $body] = viewer("?action=download&report=P-$nv");
$checks['download: strict-loopback request → 200 application/zip, filename bugreport-P-n-game-g.zip'] = $code === 200
    && stripos($head, 'Content-Type: application/zip') !== false
    && strpos($head, "filename=\"bugreport-P-$nv-game-424242.zip\"") !== false;
$tmp = tempnam(sys_get_temp_dir(), 'dlzip');
file_put_contents($tmp, $body);
$zip = new ZipArchive();
$names = [];
$gs = null;
if ($zip->open($tmp) === true) {
    for ($i = 0; $i < $zip->numFiles; $i++) $names[] = $zip->getNameIndex($i);
    $gs = $zip->getFromName('Gamestate.txt');
    $rj = json_decode((string)$zip->getFromName('report.json'), true);
    $zip->close();
}
unlink($tmp);
sort($names);
$checks['download: zip holds exactly report.json + Gamestate.txt and Gamestate.txt matches what was saved'] =
    $names === ['Gamestate.txt', 'report.json'] && $gs === $snap && ($rj['report_number'] ?? '') === "P-$nv";

[$code, , $body] = viewer("?action=download&report=P-$nv", null, false);
$checks['download: non-loopback Host → 403, no zip bytes'] = $code === 403 && substr($body, 0, 2) !== 'PK';

[$code, , $body] = viewer("?action=download&report=P-$nu");
$checks['download: unverified report → 404 no snapshot'] = $code === 404 && stripos($body, 'no snapshot') !== false;

// A cross-site form POST (the owner logged in, visiting another page) must not close or delete anything.
[$code] = viewer('', ['action' => 'delete', 'id' => "P-$nu"], true, 'https://evil.example');
$checks['delete from a foreign Origin → 403, report kept'] = $code === 403 && is_dir(LocalBugReportStorageDir() . "/P-$nu");
[$code] = viewer('', ['action' => 'resolve', 'id' => "P-$nu", 'note' => 'x'], true, null);
$checks['close with no Origin/Referer → 403'] = $code === 403;

[$code, , $body] = viewer('', ['action' => 'resolve', 'id' => "P-$nu", 'note' => 'dup']);
$st = $conn->query("SELECT status FROM swusim_bug_reports WHERE reportNumber = $nu")->fetch_row()[0] ?? '';
$checks['close via POST marks the local report resolved'] = !empty(json_decode($body, true)['success']) && $st === 'resolved';

[$code, , $body] = viewer('', ['action' => 'delete', 'id' => "P-$nu"]);
$gone = $conn->query("SELECT COUNT(*) FROM swusim_bug_reports WHERE reportNumber = $nu")->fetch_row()[0];
clearstatcache(); // the foreign-Origin check above stat'ed this folder while it still existed
$checks['delete via POST removes dir and row'] = !empty(json_decode($body, true)['success'])
    && !is_dir(LocalBugReportStorageDir() . "/P-$nu") && (int)$gone === 0;

[$code, , $body] = viewer('?rootName=SWUSim');
$checks['list: the viewer shows the local report'] = $code === 200 && strpos($body, ">P-$nv<") !== false;

LocalBugReportDelete($conn, $nv);
LocalBugReportDelete($conn, $nu);
$conn->close();

$fails = array_keys(array_filter($checks, fn($x) => $x !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
