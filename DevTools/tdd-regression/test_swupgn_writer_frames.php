<?php
// SWU-PGN writer frames: the engine's per-action hooks (GameLogBeginFrame / GameLogCommitFrame) record each action
// of a recorded game, guarded against a frame that ends on a different game, and never break the action.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_frames.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth', 'EngineActionRunner'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter', 'SchemaTestRunner', 'TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
global $gameName, $playerID;

$games = [];
// A recorded fixture game: seat 1 holds a Battlefield Marine and plenty of resources.
function FramesGame(string $suffix): string {
    global $gameName, $games;
    $gameName = 'swupgnframes_' . $suffix . '_' . getmypid();
    $games[] = $gameName;
    @mkdir('./SWUSim/Games/' . $gameName, 0777, true);
    $b = new GameStateBuilder();
    CommonSetup($b, 'grw', 'brk', ['resourceCount' => 6], ['resourceCount' => 6]);
    $b->WithActivePlayer(1)->WithCardInHandForPlayer(1, 'SOR_095');
    foreach (['SOR_101', 'SOR_102', 'SOR_103'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
    (new GameTestAdapter())->loadState($b);
    SwuPgnRecForceForTest($gameName);
    return $gameName;
}
$play = ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-0!FSM', 'chkInput' => [], 'inputText' => ''];
$run = function (array $action) use (&$gameName) {
    $GLOBALS['playerID'] = intval($action['playerID']);
    ob_start();
    $r = EngineExecuteLoadedAction($action, 'SWUSim', $gameName, ['updateCache' => false, 'disableRecording' => true]);
    ob_end_clean();
    return $r;
};
$meta = fn(string $g) => SwuPgnMetaRead($g) ?? [];

// ── One real action through the engine runner records its events ─────────────────────────────────
$g1 = FramesGame('a');
SwuPgnTestCheck(SwuPgnRecIsRecording($g1), 'the fixture game is recording');
$r = $run($play);
SwuPgnTestCheck(!empty($r['success']), 'the action succeeded', json_encode($r));
SwuPgnTestCheck(count(GetGroundArena(1)) > 0, 'the Marine is in play');
$ev = SwuPgnEventsRead($g1);
$moves = array_values(array_filter($ev, fn($e) => $e['t'] === 'MOVE' && $e['card'] === 'SOR#95' && $e['from'] === 'hand' && $e['to'] === 'ground'));
SwuPgnTestCheck(count($moves) === 1, 'events.jsonl gained the hand → ground MOVE of SOR#95', json_encode($ev));
SwuPgnTestCheck((bool)array_filter($ev, fn($e) => $e['t'] === 'STATS' && $e['card'] === 'SOR#95'), 'and its STATS');
SwuPgnTestCheck((bool)array_filter($ev, fn($e) => $e['t'] === 'EXHAUST_RESOURCES' && $e['p'] === 1), 'and the resources it paid');
SwuPgnTestCheck(preg_match('/^R\d+\.A\.\d+[a-z]*$/', $moves[0]['seq'] ?? '') === 1, 'the events carry action-phase seqs', $moves[0]['seq'] ?? '');
$state = SwuPgnStateRead($g1);
SwuPgnTestCheck(isset($state['prev']['seats'][1]) && SwuPgnLedgerByUid($state['ledger'], intval(GetGroundArena(1)[0]->UniqueID)) === 'SOR#95',
    'state.json holds the new snapshot and the ledger binds the unit');
SwuPgnTestEq($meta($g1)['recorderErrors'] ?? null, 0, 'no recorder errors');
SwuPgnTestCheck(empty($GLOBALS['SWU_PGN_RECORDING']), 'the request-scoped recording flag is cleared after the commit');

// ── Review Focus 1: a frame that ends on another game (quick rematch) writes nothing anywhere ────
$gA = FramesGame('qa');
$gB = FramesGame('qb');            // now loaded; begin on A's name, commit on B's
$gameName = $gA;
SwuPgnRecFrameBegin();
$base = &GetBase(1); $base[0]->Damage = intval($base[0]->Damage) + 3;   // a change a wrong-game commit would record
$gameName = $gB;
SwuPgnRecFrameCommit();
SwuPgnTestEq([SwuPgnEventsRead($gA), SwuPgnEventsRead($gB)], [[], []], 'a commit on a different game writes no events to either');
SwuPgnTestEq([$meta($gA)['recorderErrors'], $meta($gB)['recorderErrors']], [0, 0], 'and is not an error');

// ── A recorder that throws leaves the action successful and counts the error ─────────────────────
$g2 = FramesGame('throw');
$GLOBALS['SWUPGN_TEST_THROW'] = 'commit';
$errLog = ini_get('error_log'); ini_set('error_log', sys_get_temp_dir() . '/swupgn-frames-errors.log');
$r = $run($play);
ini_set('error_log', $errLog === false ? '' : $errLog);
unset($GLOBALS['SWUPGN_TEST_THROW']);
SwuPgnTestCheck(!empty($r['success']) && count(GetGroundArena(1)) > 0, 'the action still succeeds when the recorder throws');
SwuPgnTestEq($meta($g2)['recorderErrors'] ?? null, 1, 'recorderErrors is incremented');
SwuPgnTestCheck(empty($GLOBALS['SWU_PGN_RECORDING']), 'the recording flag is cleared even after a throw');

// ── An unrecorded game: the hooks do nothing ─────────────────────────────────────────────────────
$gameName = 'swupgnframes_off_' . getmypid(); $games[] = $gameName;
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);
$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', ['resourceCount' => 6], []); $b->WithActivePlayer(1)->WithCardInHandForPlayer(1, 'SOR_095');
(new GameTestAdapter())->loadState($b);
$r = $run($play);
SwuPgnTestCheck(!empty($r['success']) && !is_dir(SwuPgnRecDir($gameName)), 'an unrecorded game gets no Pgn folder');

// ── A request that dies mid-action (the runner never commits its frame) leaves nothing on disk ─────
// Round boundaries, checkpoints and splits record in the middle of an action; those records wait for the commit.
$g4 = FramesGame('dies');
$disk = fn() => [is_file(SwuPgnRecDir($g4) . '/events.jsonl') ? file_get_contents(SwuPgnRecDir($g4) . '/events.jsonl') : '',
    file_get_contents(SwuPgnRecDir($g4) . '/state.json'), file_get_contents(SwuPgnRecDir($g4) . '/meta.json')];
$was = $disk();
SwuPgnRecFrameBegin();
$h = &GetHand(1); $h[0]->removed = true; unset($h);          // the action changes the board…
SwuPgnRecSplit();                                            // …records part of it mid-action…
SwuPgnRecCheckpoint('u77');                                  // …saves an undo point…
_SwuPgnRecResetRequest();                                    // …and the request dies: no commit
$cpOnDisk = fn(string $k) => is_file(SwuPgnRecDir($g4) . '/checkpoints.jsonl') && str_contains(file_get_contents(SwuPgnRecDir($g4) . '/checkpoints.jsonl'), '"key":"' . $k . '"');
SwuPgnTestEq([$disk(), $cpOnDisk('u77')], [$was, false], 'a request that never commits leaves events, state, meta and checkpoints untouched');
unset($GLOBALS['SWU_PGN_TX']);
// …while a frame that commits writes everything it held.
SwuPgnRecFrameBegin();
SwuPgnRecSplit();
SwuPgnRecCheckpoint('u78');
SwuPgnRecFrameCommit();
SwuPgnTestCheck($cpOnDisk('u78') && $disk()[1] !== $was[1], 'a committed frame writes what it held');

// ── The board changed outside any frame (no runner, no recording): the next frame notices and writes it ───
$g6 = FramesGame('outside');
SwuPgnRecFrameBegin(); SwuPgnRecFrameCommit();               // the recorder knows the board…
$n6 = count(SwuPgnEventsRead($g6)); $gaps6 = intval(SwuPgnStateRead($g6)['gaps'] ?? 0);
$h = &GetHand(1); $h[0]->removed = true; unset($h);          // …something outside the runner changes it…
SwuPgnRecFrameBegin(); SwuPgnRecFrameCommit();               // …and the next frame (an action that changes nothing)
$new = array_slice(SwuPgnEventsRead($g6), $n6);
SwuPgnTestEq([array_map(fn($e) => [$e['t'], $e['from'] ?? null, $e['to'] ?? null], $new), intval(SwuPgnStateRead($g6)['gaps'] ?? 0) - $gaps6],
    [[['MOVE', 'hand', 'outsideTheGame']], 1], 'a board change made outside any frame is written at the next one, as a gap');

// ── A note that fails never breaks the action: the play happens, the failure is a recorder error ───
$g5 = FramesGame('notethrows');
$GLOBALS['SWUPGN_TEST_THROW'] = 'note';
$thrown = null;
try { $r = $run($play); } catch (Throwable $e) { $thrown = $e->getMessage(); }
unset($GLOBALS['SWUPGN_TEST_THROW']);
SwuPgnTestCheck($thrown === null && !empty($r['success']) && count(GetGroundArena(1)) > 0, 'a note that throws: the play still happens', strval($thrown));
SwuPgnTestCheck(intval($meta($g5)['recorderErrors'] ?? 0) >= 1, 'and it counts as a recorder error');

if (getenv('KEEP')) { echo implode("\n", array_map('json_encode', SwuPgnEventsRead($g1))), "\n", json_encode(SwuPgnStateRead($g1)['gapKinds'] ?? []), "\n"; }
foreach ($games as $x) exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $x));
SwuPgnTestFinish();
