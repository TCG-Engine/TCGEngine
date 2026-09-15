<?php
// Phase 1a Task 1 — the one-move lookahead spike (SWUSim/Custom/BotLookahead.php).
// Proves three things, then times it:
//   1. a lookahead really APPLIES the move (the board read inside it differs from the live board);
//   2. the live game is byte-identical afterwards (serialized zones + RNG counter), and the undo-block
//      flags and in-memory continuation globals are back;
//   3. all three wire forms dispatch: mode 10002 (FSM), mode 10001 (CustomInput), mode 100 (an answer).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lookahead_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
include_once './SWUSim/Custom/BotLookahead.php';
global $gameName, $playerID, $gRandomCounter;
$gameName = 'bottest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$build = function (callable $setup) {
    $b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []);
    $b->WithActivePlayer(1); $b->WithGamePhase('MAIN');
    $setup($b);
    $g = new GameTestAdapter(); $g->loadState($b);
    ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
    return $g;
};
$raiseAttack = function (int $player, string $attackerMz) {
    global $playerID;
    $saved = $playerID; $playerID = $player;
    ob_start();
    if (ActionMap($attackerMz) === 'ATTACK') (new DecisionQueueController())->ExecuteStaticMethods($player, '-');
    ob_end_clean();
    $playerID = $saved;
};
$live = fn(int $p) => count(array_filter(GetGroundArena($p), fn($u) => empty($u->removed)));
$snapshot = function () { global $gRandomCounter; return Versions::GetSerializedZones() . '<v0>' . $gRandomCounter; };
// GetSerializedZones() EXCLUDES the Versions zones (see SWUSim/Custom/UndoStack.php's header): seat 1's
// Versions zone is the undo log, seat 2's holds the undo cursor and the bookmarks. An action lookahead
// runs SaveUndoVersion(), which appends to the log and moves the cursor — invisible to $snapshot alone.
$undoState = function () {
    $out = [];
    for ($p = 1; $p <= 4; $p++) {
        $out[] = implode("\n", array_map(fn($e) => $e === null ? '~' : (strval($e->Version) . '|' . intval(!empty($e->removed))), GetVersions($p)));
    }
    return implode("\n--\n", $out) . "\ncursor=" . UndoCursor() . "\ncount=" . UndoStackCount();
};

// ── 1+2: an answered attack really kills inside the lookahead, and nothing survives it ─────────────
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true);   // Knight of Ren 4/4
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);   // Battlefield Marine 3/3 — dies to 4
});
$raiseAttack(1, 'myGroundArena-0');                    // two targets (Marine, base) → the prompt is pending
$pending = null;
foreach (GetDecisionQueue(1) as $d) { if (empty($d->removed)) { $pending = $d; break; } }
$check(($pending->Tooltip ?? '') === 'Choose_an_attack_target', 'fixture raised the attack-target prompt');

SetSWUVar('UNDO_BLOCKED_2', 'true');                   // an undo-block flag the restore must keep
// A global the dispatch REALLY changes: _SWUBotLookaheadDispatch() sets $playerID to the acting seat.
// (An untouched global such as gForceEnterReady proves nothing — measured: with the restore loop deleted,
// a gForceEnterReady probe stayed green because nothing in this scenario ever writes it.)
$GLOBALS['playerID'] = 2;
$before = $snapshot(); $beforeUndo = $undoState();
$inside = SWUBotLookahead(1, ['playerID' => 1, 'mode' => 100, 'cardID' => 'theirGroundArena-0'],
    fn() => ['p2Ground' => count(array_filter(GetGroundArena(2), fn($u) => empty($u->removed)))]);
$check($inside !== null && $inside['p2Ground'] === 0, 'inside the lookahead the Marine is dead — got ' . json_encode($inside));
$check($live(2) === 1, 'after the lookahead the Marine is alive again');
$check($snapshot() === $before, 'serialized zones + RNG counter are byte-identical afterwards');
$check($undoState() === $beforeUndo, 'mode 100: undo log, cursor and bookmarks untouched');
$check(GetSWUVar('UNDO_BLOCKED_2', 'false') === 'true', 'undo-block flag survived the restore');
$check(intval($GLOBALS['playerID'] ?? 0) === 2, 'in-memory globals restored ($playerID back to 2 after a seat-1 lookahead)');
$GLOBALS['playerID'] = 1;

// ── 3: the other two wire forms dispatch and restore ───────────────────────────────────────────────
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
    $b->WithCardInHandForPlayer(1, 'SOR_095');
    for ($i = 0; $i < 3; $i++) $b->WithControlledResourceForPlayer(1, 'SOR_095', 1, true);
});
$before = $snapshot(); $beforeUndo = $undoState();
$inside = SWUBotLookahead(1, ['playerID' => 1, 'mode' => 10002, 'cardID' => 'myHand-0!FSM!'],
    fn() => ['p1Ground' => count(array_filter(GetGroundArena(1), fn($u) => empty($u->removed)))]);
$check($inside !== null && $inside['p1Ground'] === 2, 'mode 10002: the played unit is on the board inside — got ' . json_encode($inside));
$check($live(1) === 1 && $snapshot() === $before, 'mode 10002: restored byte-identically');
$check($undoState() === $beforeUndo, 'mode 10002: undo log, cursor and bookmarks untouched');

$before = $snapshot(); $beforeUndo = $undoState();
$inside = SWUBotLookahead(1, ['playerID' => 1, 'mode' => 10001, 'cardID' => 'InitiativeCounter-0!CustomInput!TakeInitiative'],
    fn() => ['ic' => strval(GetInitiativeCounter())]);
$check($inside !== null && str_ends_with($inside['ic'], '_CLAIMED'), 'mode 10001: initiative claimed inside — got ' . json_encode($inside));
$check(!str_ends_with(strval(GetInitiativeCounter()), '_CLAIMED') && $snapshot() === $before, 'mode 10001: restored byte-identically');
$check($undoState() === $beforeUndo, 'mode 10001: undo log, cursor and bookmarks untouched');

$check(SWUBotLookahead(1, ['playerID' => 1, 'mode' => 99999, 'cardID' => 'x'], fn() => []) === null,
    'an unknown wire form returns null');

// ── timing ─────────────────────────────────────────────────────────────────────────────────────────
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$times = [];
for ($i = 0; $i < 20; $i++) {
    $t0 = hrtime(true);
    SWUBotLookahead(1, ['playerID' => 1, 'mode' => 100, 'cardID' => 'theirGroundArena-0'], fn() => ['x' => 1]);
    $times[] = (hrtime(true) - $t0) / 1e6;
}
sort($times);
$median = ($times[9] + $times[10]) / 2;
echo sprintf("lookahead timing over 20 runs: median %.2f ms, min %.2f ms, max %.2f ms\n", $median, $times[0], $times[19]);

array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
