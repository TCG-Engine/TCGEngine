<?php
// import-bugreport.php — CLI: import a Petranaki bug-report package into the local SWUSim/Games/<same game number>/
// (LocalBugReportImport; docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md).
// The owner downloads the package from zzBugReportViewer.php on petranaki.net into OTMTCGE/.bugreports/ (gitignored).
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php SWUSim/DevTools/import-bugreport.php <P-n | path.zip>
//   → prints JSON {success, gameName, backup, cacheRefreshed} or {error}; exit 0 on success.
//
// An existing local game with that number is moved to SWUSim/Games/<n>.bak-<YmdHis> first. Afterwards Apache's APCu
// copy of game <n> (if any — a local game with the same number) is replaced through the loopback-only
// bugreport-cache-refresh.php, because ParseGamestate reads APCu before the file and the CLI cannot reach Apache's APCu.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL & ~E_DEPRECATED);

$root = dirname(__DIR__, 2);   // …/SWUSim/DevTools → repo root
chdir($root);
require_once './LocalBugReportLib.php';

function _importRespond(int $exit, array $payload): void { echo json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n"; exit($exit); }

$arg = strval($argv[1] ?? '');
if ($arg === '') _importRespond(2, ['error' => 'Usage: import-bugreport.php <P-n | path/to/bugreport-P-n-game-g.zip>']);

if (is_file($arg)) {
    $zipPath = $arg;
} else {
    $n = LocalBugReportParseNumber($arg);
    if ($n === null) _importRespond(2, ['error' => "Not a report number or a file: $arg"]);
    $matches = glob("./.bugreports/bugreport-P-$n-*.zip") ?: [];
    if (count($matches) === 0) {
        _importRespond(3, ['error' => "No .bugreports/bugreport-P-$n-*.zip — download P-$n's package from the bug report viewer on petranaki.net into OTMTCGE/.bugreports/ first."]);
    }
    if (count($matches) > 1) _importRespond(3, ['error' => "More than one package for P-$n; pass the file path.", 'candidates' => $matches]);
    $zipPath = $matches[0];
}

$r = LocalBugReportImport($zipPath, './SWUSim/Games', time());
if (!$r['ok']) _importRespond(4, ['error' => $r['error']] + ($r['backup'] ? ['backup' => $r['backup']] : []));

$ch = curl_init('http://localhost/TCGEngine/SWUSim/DevTools/bugreport-cache-refresh.php?game=' . rawurlencode($r['gameName']));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$refresh = json_decode((string)curl_exec($ch), true);
curl_close($ch);

_importRespond(0, ['success' => true, 'gameName' => $r['gameName'], 'backup' => $r['backup'],
    'cacheRefreshed' => (bool)($refresh['refreshed'] ?? false)]);
