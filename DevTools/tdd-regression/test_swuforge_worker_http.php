<?php
// SWU Forge delivery worker page: answers only the box itself, and delivers what is due when it does.
// RUN VIA CLI (it calls Apache inside the container):
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swuforge_worker_http.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './SWUSim/SWUForgeDeliverWorker.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

check('the worker answers the box itself', SWUForgeWorkerAllowed('apache2handler', '127.0.0.1') && SWUForgeWorkerAllowed('apache2handler', '::1')
    && SWUForgeWorkerAllowed('cli', ''));
check('and nobody else', !SWUForgeWorkerAllowed('apache2handler', '203.0.113.9') && !SWUForgeWorkerAllowed('apache2handler', ''));

$ch = curl_init('http://localhost/TCGEngine/SWUSim/SWUForgeDeliverWorker.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60]);
$raw = (string)curl_exec($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
$j = json_decode($raw, true);
check('over HTTP from the box it runs and reports how many it attempted', $http === 200 && is_array($j) && is_int($j['attempted'] ?? null), "$http $raw");

foreach ($MSGS as $m) echo $m, "\n";
echo $FAIL === 0 ? "PASS: test_swuforge_worker_http.php — all $PASS checks passed\n" : "FAIL: test_swuforge_worker_http.php — $FAIL of " . ($PASS + $FAIL) . " checks failed\n";
exit($FAIL === 0 ? 0 : 1);
