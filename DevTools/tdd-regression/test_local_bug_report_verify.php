<?php
// docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_local_bug_report_verify.php
//
// LocalBugReportVerifySeat decides whether a SWUSim bug report is "verified" (game found + seat key accepted →
// snapshot kept) or "unverified" (saved anyway, no snapshot, with the reason). Through Apache the dev box accepts
// EVERY seat key (DEVENV=true in the container env → SimGameValidateSeatAuth returns true), so the wrong-key branch
// is only observable here, on the CLI with DEVENV off — exactly the prod condition.
// Builds a board with the Test Schema Editor over loopback. Seat keys live in APCu, and the CLI's APCu is not
// Apache's, and a schema board's keys are EMPTY (any key passes), so the test registers real seat keys for its
// board in-process — hence apc.enable_cli=1.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', '1');
chdir('/var/www/html/TCGEngine');
require_once './LocalBugReportLib.php';

$ch = curl_init('http://localhost/TCGEngine/SWUSim/TestSchemaSetup.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['schema' => file_get_contents('./SWUSim/Tests/Visual/GameLog_UndoneAndDeckLines.md')]]);
$game = (string)(json_decode((string)curl_exec($ch), true)['gameName'] ?? '');
curl_close($ch);

putenv('DEVENV=false');
$checks = [];
$checks['precondition: APCu on in this CLI (-d apc.enable_cli=1)'] = function_exists('apcu_enabled') && apcu_enabled();
SimGameWriteAuthKeys('SWUSim', $game, ['p1' => 'seat-one-key', 'p2' => 'seat-two-key'] + SimGameDefaultAuthKeys());
$checks['precondition: schema board built'] = $game !== '';
$checks['precondition: dev bypass is off'] = !SimGameIsDevelopmentEnvironment();

$seat1 = NormalizeViewerIdentity('1', SimGameMaxSeats('SWUSim'));
$ok = LocalBugReportVerifySeat($game, $seat1, 'seat-one-key');
$checks['verify: right key → verified, gamestate captured'] = $ok['verified'] === true && $ok['reason'] === ''
    && strlen($ok['gamestateText']) > 100;

$bad = LocalBugReportVerifySeat($game, $seat1, 'seat-two-key');
$checks['verify: the other seat key → unverified "seat key not recognised", no gamestate'] = $bad['verified'] === false
    && $bad['reason'] === 'seat key not recognised' && $bad['gamestateText'] === '';

$none = LocalBugReportVerifySeat($game, $seat1, '');
$checks['verify: no key → unverified'] = $none['verified'] === false && $none['reason'] === 'seat key not recognised';

$gone = LocalBugReportVerifySeat('999999999', $seat1, 'testschema');
$checks['verify: unknown game → "game not found"'] = $gone['verified'] === false && $gone['reason'] === 'game not found' && $gone['gamestateText'] === '';

$junk = LocalBugReportVerifySeat('../../etc', $seat1, 'testschema');
$checks['verify: non-numeric game → "game not found"'] = $junk['verified'] === false && $junk['reason'] === 'game not found';

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
exit(empty($fails) ? 0 : 1);
