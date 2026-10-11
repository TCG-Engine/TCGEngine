<?php
// Twin Suns "Fill Seat with Bot" — the GAME side of a room game with bot seats (SWUSim/docs/todo-twinsuns-fill-bot.md,
// step 1). A room game with bots is a NORMAL multiplayer game, not Arenabot: SWUGameMode() stays '' (so the
// inactivity clock and undo consent keep working for the humans), and a separate never-cleared flag tells the bot
// controller which seats it drives. SWUMarkBotSeats() is the one setter SWUSetupGame() calls.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/twinsuns_bot_seats_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
require_once './SWUSim/BotController.php';
require_once './SWUSim/Custom/InactivityClock.php';
if (!function_exists('WriteCache')) { function WriteCache(...$a) {} }
if (!function_exists('GamestateUpdated')) { function GamestateUpdated(...$a) {} }
// A bot step that runs writes the gamestate, which targets SWUSim/Games/<game>/ (the bootstrap makes only ./Games/).
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);
register_shutdown_function(function () {
    $d = './SWUSim/Games/' . strval($GLOBALS['gameName'] ?? '');
    if (strpos(basename($d), 'bottest_') !== 0 || !is_dir($d)) return;
    array_map('unlink', glob($d . '/{,.}[!.]*', GLOB_BRACE) ?: []);
    @rmdir($d);
});

// A 3-seat Twin Suns board in the action phase. $active owes the free-play action; seat 3 has a ready unit.
$ts = function (int $active) use ($build) {
    $build(function ($b) use ($active) {
        CommonSetupFarSeat($b, 3, 'grw');
        $b->WithSeatOrder('123')->WithLiveSeats('123');
        $b->WithActivePlayer($active);
        $b->WithGroundUnitForPlayer(3, 'SOR_095', true);
    });
};

// ── control: the bot-seat list is inert without the flag (today's behaviour outside Arenabot) ──
$ts(3);
SetSWUBotPlayers([3]);
$check(SWUGameMode() === '' && GetSWUBotPlayers() === [], 'control: no flag -> no bot seats, even with the list stored');
$check(BotControllerPendingPlayerForClient() === 0, 'control: no flag -> the controller owes nothing');

// ── the flag ──
$ts(3);
SWUMarkBotSeats([3 => 'heuristic-normal']);
$check(SWUGameMode() === '', 'a room game with bots is NOT a game mode (clock + undo consent stay on)');
$check(GetSWUBotPlayers() === [3], 'flag: GetSWUBotPlayers reads the bot seats; got ' . json_encode(GetSWUBotPlayers()));
$check(GameBotControllerMode() === 'bot' && GetBotControllerPlayers() === [3], 'flag: the client bot controller is enabled for seat 3');
$check(BotControllerPendingPlayerForClient() === 3, 'flag: seat 3 (the bot) owes the free-play action');
$check(SWUBotActiveChooserProfile(3) === 'heuristic-normal', 'flag: seat 3 plays its own stored profile; got ' . SWUBotActiveChooserProfile(3));
$r = ProcessBotControllerStep(1, 'SWUSim', $gameName);
$check(!empty($r['applied']), 'flag: a step requested by a HUMAN seat moves the bot; got ' . json_encode($r));

$ts(1);
SWUMarkBotSeats([3 => 'heuristic-normal']);
$check(BotControllerPendingPlayerForClient() === 0, 'a human (seat 1) owes the action -> the bot owes nothing');
$r = ProcessBotControllerStep(1, 'SWUSim', $gameName);
$check(empty($r['applied']), 'a step while a human owes the action moves nothing');

// ── a seat-3 PROMPT reaches the bot as that prompt ──
// Found by the Twin Suns self-play harness (DevTools/SWUSimTwinSunsSelfPlay.php) on its first game: the decision
// bridge (DevTools/TestAutomationBridge.php BridgeEnumerateLegalActionsLoaded) only looked at seats 1-2, so seat 3's
// mulligan YES/NO fell through to the TURN player's free-play actions — every one a no-op — and the bot gave up.
$ts(1);
SWUMarkBotSeats([3 => 'heuristic-normal']);
DecisionQueueController::AddDecision(3, 'YESNO', '-', 1, tooltip: 'Collect_bounty?');
$legal = SWUBotLegalActions($gameName, 3);
$answers = array_map(fn($a) => intval($a['mode'] ?? 0) . ':' . strval($a['cardID'] ?? ''), $legal['actions'] ?? []);
$check(($legal['kind'] ?? '') === 'decision' && ($legal['decisionType'] ?? '') === 'YESNO' && count($answers) === 2,
    "a seat-3 YESNO is enumerated as a YES/NO decision for seat 3: " . json_encode([$legal['kind'] ?? '', $legal['decisionType'] ?? '', $answers]));
$check(BotControllerPendingPlayerForClient() === 3, 'and the controller sends the step to seat 3');
$r = ProcessBotControllerStep(1, 'SWUSim', $gameName);
$check(!empty($r['applied']), 'the bot answers it: ' . json_encode($r));

// ── an eliminated bot seat drops out ──
$ts(1);
SWUMarkBotSeats([3 => 'heuristic-normal']);
SetLiveSeats('12');
$check(GetSWUBotPlayers() === [], 'an eliminated bot seat is no longer driven; got ' . json_encode(GetSWUBotPlayers()));

// ── the inactivity clock stays on for the humans, and bots never vote ──
$ts(1);
SWUMarkBotSeats([3 => 'heuristic-normal']);
$facts = SWUClockFacts();
$check(in_array(3, $facts['bots'], true), 'clock facts list seat 3 as a bot');
$check(SWUVoterSeatsFor(1) === [2], 'kick voters against seat 1 exclude the bot (loaded path); got ' . json_encode(SWUVoterSeatsFor(1)));
$check(SWUVoterSeatsFor(1, $facts) === [2], 'kick voters exclude the bot (cheap path, from facts); got ' . json_encode(SWUVoterSeatsFor(1, $facts)));
$check(SWUVotesNeededFor(1, $facts) === 1, 'the one human opponent decides the kick alone; got ' . SWUVotesNeededFor(1, $facts));
SetSWUBotPlayers([]);
$check(SWUVoterSeatsFor(1) === [2, 3], 'control: with no bots both opponents vote');

// ── undo consent: a request nobody human can answer must not be asked ──
$ts(1);
SWUMarkBotSeats([3 => 'heuristic-normal']);
$check(SWUUndoNeedsConsent(1, UndoCursor(), 'phase') === true, 'control: a human opponent remains -> Undo Phase still asks');
$ts(1);
SWUMarkBotSeats([2 => 'heuristic-normal', 3 => 'heuristic-normal']);
$check(SWUUndoNeedsConsent(1, UndoCursor(), 'phase') === false, 'every opponent is a bot -> the undo is free (nobody could answer it)');

// ── a step Undo skips back past the BOT's replies, as in Arenabot ──
// Otherwise the bot, owing its move again, replays it within ~100 ms and the human can never reach their own action
// (owner ruling 2026-09-14, undo/BotPracticeUndoSkipsTheBotsMoves.md). P1 plays, the bot (seat 2, next in turn order) plays; P1's step
// Undo targets the record of P1's OWN play, not the bot's.
include_once './SWUSim/Custom/BotLookahead.php';
$undoBoard = function (array $bots) use ($build) {
    $build(function ($b) {
        CommonSetupFarSeat($b, 3, 'grw');
        $b->WithSeatOrder('123')->WithLiveSeats('123');
        $b->WithActivePlayer(1);
        foreach ([1, 2] as $s) {
            for ($i = 0; $i < 4; $i++) $b->WithControlledResourceForPlayer($s, 'SOR_095', $s);
            $b->WithCardInHandForPlayer($s, 'SOR_095');
        }
    });
    if ($bots) SWUMarkBotSeats($bots);
};
$undoBoard([2 => 'heuristic-normal']);
$act(1, 10002, 'myHand-0!FSM!');
$c1 = UndoCursor();
$r = ProcessBotControllerStep(1, 'SWUSim', $gameName);      // the bot (seat 2) answers with a real move
$top = UndoCursor();
$topRec = $top >= 0 ? UndoStackRead($top) : null;
$check(!empty($r['applied']) && $top > $c1 && $topRec !== null && intval(UndoRecordParse($topRec)['seat']) === 2,
    "fixture: the bot's move is the newest undo record (cursor $c1 -> $top)");
$target = SWUComputeUndoTarget('step', 1);
$rec = $target >= 0 ? UndoStackRead($target) : null;
$check($target < $top && $rec !== null && intval(UndoRecordParse($rec)['seat']) === 1,
    "bot seat: P1's step Undo skips the bot's move to P1's OWN play (target $target, top $top)");

bot_test_finish();
