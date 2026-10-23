<?php
// SWU-PGN writer undo: an undo (or a bookmark load) cuts the records it retracted, rewinds the ledger and counters, and
// leaves an UNDO mark; a bookmark that reopens a finished game un-finishes the recording.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_swupgn_writer_undo.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
global $gameName, $playerID;
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;   // a private lobby: undo and bookmarks need no consent

$gameName = 'swupgnundo_' . getmypid();
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);
$b = new GameStateBuilder();
CommonSetup($b, 'grw', 'brk', ['resourceCount' => 8], ['resourceCount' => 8]);
$b->WithActivePlayer(1)->WithCardInHandForPlayer(1, 'SOR_095')->WithCardInHandForPlayer(1, 'SOR_046');
foreach (['SOR_101', 'SOR_102'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
$a = new GameTestAdapter(); $a->loadState($b);
SwuPgnRecForceForTest($gameName);
UndoStackClear();

$frame = function (callable $fn) {
    SwuPgnRecFrameBegin();
    ob_start();
    try { $fn(); } finally { ob_end_clean(); }
    SwuPgnRecFrameCommit();
};
$seqs = fn() => array_column(SwuPgnEventsRead($GLOBALS['gameName']), 'seq');
$meta = fn() => SwuPgnMetaRead($GLOBALS['gameName']) ?? [];

// Play, undo, play again: the retracted records are gone, the replay re-issues the same seqs, an UNDO marks the cut.
$frame(fn() => $a->playCardFromHand(1, 0));
$afterPlay = SwuPgnEventsRead($gameName);
SwuPgnTestCheck((bool)array_filter($afterPlay, fn($e) => $e['t'] === 'PLAY'), 'the play is recorded');
$firstCut = $afterPlay[0]['seq'];
$frame(fn() => SWUDoUndo(1, 'step'));
$afterUndo = SwuPgnEventsRead($gameName);
SwuPgnTestCheck(count(GetHand(1)) === 2 && count(GetGroundArena(1)) === 0, 'the engine undid the play');
SwuPgnTestEq(array_column($afterUndo, 't'), ['UNDO'], 'the play\'s records are cut; only the UNDO mark remains');
SwuPgnTestEq($afterUndo[0] ?? null, ['seq' => "$firstCut-undo", 't' => 'UNDO', 'at' => $firstCut, 'by' => 1], 'UNDO {at: the first cut seq, by} at <at>-undo');
SwuPgnTestEq($meta()['undos'] ?? null, 1, 'meta.undos counts it');
$frame(fn() => $a->playCardFromHand(1, 0));
$s = $seqs();
SwuPgnTestEq(count($s), count(array_unique($s)), 'no duplicate seqs after the replay');
SwuPgnTestCheck(in_array($firstCut, $s, true), 'the replay re-issues the cut seq');
$fold = SwuPgnFold(SwuPgnParse(SwuPgnTestFile(SwuPgnEventsRead($gameName)))['events']);
SwuPgnTestEq(count($fold['players'][1]['cards'] ?? []), 1, 'the fold holds the replayed unit once');
SwuPgnTestEq(SwuPgnStateRead($gameName)['gaps'] ?? null, 0, 'an undone frame leaves no gaps (the restore is not a change)');

// A second undo back to the same point: an UNDO's seq is exactly <at>-undo (§9.1) and seqs are unique, so the mark
// already there stands for both; the header's Undos count is the authority (§10.2: it MAY exceed the UNDO records).
$frame(fn() => SWUDoUndo(1, 'step'));
$s = $seqs();
SwuPgnTestEq(array_values(array_filter($s, fn($x) => str_starts_with($x, "$firstCut-undo"))), ["$firstCut-undo"], 'a repeat cut to the same point writes no second mark');
SwuPgnTestEq(array_values(array_filter(SwuPgnValidate(SwuPgnParse(SwuPgnTestFile(SwuPgnEventsRead($gameName))))['errors'], fn($e) => $e['code'] === 'undo.seq')), [],
    'and every UNDO seq is its at plus -undo');
SwuPgnTestEq(count($s), count(array_unique($s)), 'still no duplicate seqs');
SwuPgnTestEq($meta()['undos'] ?? null, 2, 'meta.undos = 2');

// ── Review Focus 2: a bookmark taken before the end reopens a finished game ──────────────────────
$frame(fn() => $a->playCardFromHand(1, 0));
$frame(fn() => SWUTakeBookmark(1, 'before the end', 'SWUSim', $GLOBALS['gameName']));
$bmEvents = count(SwuPgnEventsRead($gameName));
$frame(fn() => $a->playCardFromHand(1, 0));                    // a later action
DecisionQueueController::StoreVariable('GAMEOVER_WINNER', '1'); // …and the game ends
$m = $meta(); $m['finished'] = true; SwuPgnMetaWrite($gameName, $m);
@mkdir(SwuPgnRecDir($gameName) . '/out', 0777, true);
file_put_contents(SwuPgnRecDir($gameName) . '/out/all.swupgn', "stale\n");
SwuPgnTestCheck(!SwuPgnRecIsRecording($gameName), 'a finished game is not recording');
$frame(fn() => SWULoadBookmark(1, 1, 'SWUSim', $GLOBALS['gameName']));
SwuPgnTestEq(SWUGetGameWinner(), 0, 'the bookmark cleared the winner');
SwuPgnTestEq($meta()['finished'] ?? null, false, 'the recording is reopened');
SwuPgnTestCheck(!is_dir(SwuPgnRecDir($gameName) . '/out'), 'the stale finished files are deleted');
$ev = SwuPgnEventsRead($gameName);
SwuPgnTestEq(count($ev), $bmEvents + 1, 'the events are cut back to the bookmark, plus its UNDO mark');
SwuPgnTestEq(end($ev)['t'] ?? null, 'UNDO', 'the bookmark load is an UNDO');
SwuPgnTestCheck(SwuPgnRecIsRecording($gameName), 'recording continues after the reopen');

// A missing checkpoint: the UNDO mark is still written, nothing is cut, and it counts as a recorder error.
$errLog = ini_get('error_log'); ini_set('error_log', sys_get_temp_dir() . '/swupgn-undo-errors.log');
$n = count(SwuPgnEventsRead($gameName));
$frame(fn() => SwuPgnRecRestore('u999', 1));
ini_set('error_log', $errLog === false ? '' : $errLog);
$ev = SwuPgnEventsRead($gameName);
SwuPgnTestEq([count($ev), end($ev)['t'] ?? null], [$n + 1, 'UNDO'], 'a missing checkpoint: no cut, an UNDO mark');
SwuPgnTestEq($meta()['recorderErrors'] ?? null, 1, 'and a recorder error');

exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $gameName));

// ── Undoing a bookmark LOAD walks back up the abandoned line (SWULoadBookmark pushes a 'load' undo point): the
// recording must come back with it — the play the load cut is in the file again, matching the board.
$gameName = 'swupgnundoload_' . getmypid();
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);
$b = new GameStateBuilder();
CommonSetup($b, 'grw', 'brk', ['resourceCount' => 8], ['resourceCount' => 8]);
$b->WithActivePlayer(1)->WithCardInHandForPlayer(1, 'SOR_095')->WithCardInHandForPlayer(1, 'SOR_046');
foreach (['SOR_101', 'SOR_102'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
$a = new GameTestAdapter(); $a->loadState($b);
SwuPgnRecForceForTest($gameName);
UndoStackClear();
$frame(fn() => SWUTakeBookmark(1, 'start', 'SWUSim', $GLOBALS['gameName']));
$frame(fn() => $a->playCardFromHand(1, 0));
$withPlay = array_values(array_filter(SwuPgnEventsRead($gameName), fn($e) => $e['t'] !== 'UNDO'));
$frame(fn() => SWULoadBookmark(1, 1, 'SWUSim', $GLOBALS['gameName']));
$frame(fn() => SWUDoUndo(1, 'step'));   // rewinds the load itself
$live = count(array_filter(GetGroundArena(1), fn($o) => empty($o->removed)));
$ev = SwuPgnEventsRead($gameName);
$fold = SwuPgnFold(SwuPgnParse(SwuPgnTestFile($ev))['events']);
SwuPgnTestEq([$live, count($fold['players'][1]['cards'] ?? [])], [1, 1], 'undoing a bookmark load: the board and the file both have the unit back');
SwuPgnTestEq(array_values(array_filter($ev, fn($e) => $e['t'] !== 'UNDO')), $withPlay, 'the abandoned line\'s records are restored as they were');
SwuPgnTestEq(count(array_column($ev, 'seq')), count(array_unique(array_column($ev, 'seq'))), 'seqs stay unique');
SwuPgnTestEq($meta()['undos'] ?? null, 2, 'both rewinds counted');
// …and the restored line carries on: P2's next action is recorded after it, with fresh seqs.
$frame(fn() => $a->passAction(2));
$ev = SwuPgnEventsRead($gameName);
SwuPgnTestEq([end($ev)['t'] ?? null, end($ev)['p'] ?? null], ['PASS', 2], 'play continues on the restored line');
SwuPgnTestEq(count(array_column($ev, 'seq')), count(array_unique(array_column($ev, 'seq'))), 'with unique seqs');
SwuPgnTestEq(array_values(array_filter(SwuPgnValidate(SwuPgnParse(SwuPgnTestFile($ev)))['errors'], fn($e) => $e['code'] !== 'undo.count')), [], 'and it validates');
exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $gameName));
SwuPgnTestFinish();
