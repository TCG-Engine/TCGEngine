<?php
// SWU-PGN export, the seat decision with REAL seat keys and the dev-container bypass off: a player gets exactly their own
// view; another seat's number, a wrong key, a spectator or a bad playerID gets 403. (test_swupgn_export_http.php runs
// the real endpoint end to end, where DEVENV skips key checks.)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_swupgn_export.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth', 'ViewerIdentity'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
require_once './SWUSim/Custom/SwuPgnViewerStore.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
global $gameName;
$GLOBALS['SWUPGN_HEALTH_FILE'] = sys_get_temp_dir() . '/swupgn-export-health-' . getmypid() . '.jsonl';

$gameName = 'swupgnexp_' . getmypid();
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);
$b = new GameStateBuilder();
CommonSetup($b, 'grw', 'brk', ['resourceCount' => 4], ['resourceCount' => 4, 'baseDamage' => 27]);
$b->WithActivePlayer(1)->WithGroundUnitForPlayer(1, 'SOR_095');
foreach (['SOR_101', 'SOR_102'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
$a = new GameTestAdapter(); $a->loadState($b);
SwuPgnRecForceForTest($gameName);
$keys = [1 => 'KEY_ONE_' . getmypid(), 2 => 'KEY_TWO_' . getmypid()];
$lobby = new stdClass(); $lobby->isPrivate = false;
$lobby->players = [new class($keys[1]) { public function __construct(private $k) {} public function getAuthKey() { return $this->k; } public function getGamePlayerID() { return 1; } },
                   new class($keys[2]) { public function __construct(private $k) {} public function getAuthKey() { return $this->k; } public function getGamePlayerID() { return 2; } }];
SwuPgnTestCheck(SimGameWriteAuthKeysFromLobby('SWUSim', $gameName, $lobby), 'the game has real seat keys');
$req = fn($pid, $key) => SwuPgnExportRequest(['gameName' => $gameName, 'playerID' => $pid, 'authKey' => $key]);
SwuPgnTestEq($req(1, $keys[1])['status'] ?? null, 404, 'before the end: 404');

SwuPgnRecFrameBegin(); ob_start(); $a->declareAttack(1, 'myGroundArena-0', 'theirBase-0'); ob_end_clean(); SwuPgnRecFrameCommit();
SwuPgnTestEq(SWUGetGameWinner(), 1, 'the game is over');

// Production auth semantics: the dev short-circuit off for these calls (as test_swusim_authkeys.php does).
$devWas = getenv('DEVENV'); $hostWas = $_SERVER['HTTP_HOST'] ?? null; $addrWas = $_SERVER['REMOTE_ADDR'] ?? null;
putenv('DEVENV=false'); $_SERVER['HTTP_HOST'] = 'prod.invalid'; $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
SwuPgnTestCheck(!SimGameIsDevelopmentEnvironment(), 'precondition: the dev bypass is off');
foreach ([1, 2] as $s) {
    $r = $req($s, $keys[$s]);
    SwuPgnTestCheck(!empty($r['ok']) && (SwuPgnParse($r['pgn'])['headers']['Perspective'] ?? '') === "P$s", "seat $s with its key gets its own view (P$s)");
}
SwuPgnTestEq($req(2, $keys[1])['status'] ?? null, 403, 'seat 2\'s number with seat 1\'s key: 403');
SwuPgnTestEq($req(1, $keys[2])['status'] ?? null, 403, 'seat 1\'s number with seat 2\'s key: 403');
SwuPgnTestEq($req(1, 'WRONG')['status'] ?? null, 403, 'a wrong key: 403');
SwuPgnTestEq($req(1, '')['status'] ?? null, 403, 'no key: 403');
SwuPgnTestEq($req('S', $keys[1])['status'] ?? null, 403, 'a spectator: 403');
foreach (['all', '0', '3', '1x'] as $bad) SwuPgnTestEq($req($bad, $keys[1])['status'] ?? null, 403, "playerID=$bad: 403");
SwuPgnTestEq(SwuPgnExportRequest(['gameName' => '../x', 'playerID' => 1, 'authKey' => $keys[1]])['status'] ?? null, 400, 'a bad game name: 400');
putenv($devWas === false ? 'DEVENV' : 'DEVENV=' . $devWas);
if ($hostWas === null) unset($_SERVER['HTTP_HOST']); else $_SERVER['HTTP_HOST'] = $hostWas;
if ($addrWas === null) unset($_SERVER['REMOTE_ADDR']); else $_SERVER['REMOTE_ADDR'] = $addrWas;

$r = SwuPgnExportFor($gameName, 1);
SwuPgnTestCheck(preg_match('/^petranaki_[a-z0-9-]+_vs_[a-z0-9-]+_\d{4}-\d\d-\d\d\.swupgn$/', $r['filename'] ?? '') === 1, 'the filename', $r['filename'] ?? '');
SwuPgnTestEq(SwuPgnExportFor($gameName, 3)['status'] ?? null, 404, 'no seat-3 file exists to hand out');

exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $gameName)); @unlink($GLOBALS['SWUPGN_HEALTH_FILE']);
SwuPgnTestFinish();
