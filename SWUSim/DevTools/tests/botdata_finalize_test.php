<?php
// BotData finalize: meta.json (the join key) and replay.json (approach B, the exactness backstop).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/botdata_finalize_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/Custom/BotDataSnapshot.php';
include_once './SWUSim/Custom/BotDataRecorder.php';

$mkBot = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('ASH_009'); $b->MyBase('ASH_019');
        $b->TheirLeader('HMW_008'); $b->TheirBase('HMW_021');
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithCardInHandForPlayer(1, 'ASH_248');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_BOTPRACTICE');
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
    SetSWUBotPlayers([2]);   // human-vs-bot: the recorder ignores an all-bot game (see the recorder test)
};
$wipe = function () { $d = SWUBotDataDir(); if ($d !== '') { array_map('unlink', glob($d . '/*') ?: []); @rmdir($d); } };

$mkBot(); $wipe(); $mkBot();
$act(1, 10002, 'myHand-0!FSM!');
SWUBotDataFinalize(1);
$dir = SWUBotDataDir();

$meta = json_decode(strval(@file_get_contents($dir . '/meta.json')), true);
$check(is_array($meta), 'meta.json is written and parses');
$check(intval($meta['winner'] ?? -1) === 1, 'the winner passed in is recorded; got ' . json_encode($meta['winner'] ?? null));
$check(($meta['leader']['1'] ?? '') === 'ASH_009' && ($meta['base']['1'] ?? '') === 'ASH_019', 'meta carries seat 1 leader/base');
$check(($meta['leader']['2'] ?? '') === 'HMW_008', 'meta carries seat 2 leader');
$check(array_key_exists('rounds', $meta), 'meta carries the round count');
$check(array_key_exists('gameLog', $meta), 'the CUMULATIVE GameLog lives here, once — never in a snapshot');
$check(($meta['botStyle'] ?? null) !== null, 'meta carries the bot style');
$check(($meta['finished'] ?? null) === true, 'a finalized game is marked finished');
// ⚠ Two different things, and conflating them makes a card-level report silently wrong: what is LEFT
// in the deck at game end vs what the player BROUGHT. Found in the Task 8 acceptance run, where a
// 50-card deck showed as "34 cards".
$check(is_array($meta['deckRemaining']['1'] ?? null), 'meta carries what is LEFT in seat 1 deck');
$check(!array_key_exists('deck', $meta), "the ambiguous 'deck' key is gone");
$check(array_key_exists('deckList', $meta), 'meta carries the STARTING deck lists');

// NO IDENTITY anywhere in the directory.
$blob = '';
foreach (glob($dir . '/*') ?: [] as $f) $blob .= file_get_contents($f);
foreach (['authKey', 'userId', 'username'] as $bad) {
    $check(stripos($blob, $bad) === false, "no '$bad' appears anywhere under BotData/");
}

$rep = json_decode(strval(@file_get_contents($dir . '/replay.json')), true);
$check(is_array($rep) && array_key_exists('commands', $rep), 'replay.json carries the replay commands');

// Idempotent, like the commit point it hangs off.
$before = file_get_contents($dir . '/meta.json');
SWUBotDataFinalize(2);
$check(file_get_contents($dir . '/meta.json') === $before, 'a second finalize does not overwrite the first');

// ── ⚠ FINALIZE MUST BE SUPPRESSED INSIDE A LOOKAHEAD (review finding #1, CRITICAL) ───────────
// SWUBotLookahead restores the GAMESTATE byte-identically — it does not restore the FILESYSTEM. Rule
// 'break-lethal' (BotRules.php) dispatches every candidate attack through the lookahead, so a
// HYPOTHETICAL lethal reaches CombatLogic's SWUDeclareGameWinner() and would write meta.json with the
// wrong winner. The is_file() idempotence guard then blocks the REAL result forever — silently
// inverting the outcome label on exactly the close games worth analysing.
$mkBot(); $wipe(); $mkBot();
$dirL = SWUBotDataDir();
SWUBotDataEnterLookahead();
SWUBotDataFinalize(2);
SWUBotDataExitLookahead();
$check(!is_file($dirL . '/meta.json'), 'a lookahead-triggered win writes NO meta.json');
$check(!is_file($dirL . '/replay.json'), 'and no replay.json');
SWUBotDataFinalize(1);
$metaL = json_decode(strval(@file_get_contents($dirL . '/meta.json')), true);
$check(intval($metaL['winner'] ?? -1) === 1, 'and the REAL result still records afterwards; got ' . json_encode($metaL['winner'] ?? null));

// A meta.json that failed to encode must not block a retry (review finding #10).
$mkBot(); $wipe(); $mkBot();
$dirE = SWUBotDataDir();
@mkdir($dirE, 0777, true);
file_put_contents($dirE . '/meta.json', '');      // a zero-byte file, as a failed encode would leave
SWUBotDataFinalize(1);
$check(filesize($dirE . '/meta.json') > 0, 'an EMPTY meta.json does not block finalize; got ' . filesize($dirE . '/meta.json') . ' bytes');

// ── REVIEW FOCUS 2 — an ABANDONED game (finalize never runs) is still readable ───────────────
$mkBot(); $wipe(); $mkBot();
$act(1, 10002, 'myHand-0!FSM!');
$dir2 = SWUBotDataDir();
$check(is_file($dir2 . '/states.jsonl'), 'an unfinished game still has its trajectory');
$check(!is_file($dir2 . '/meta.json'), 'and no meta.json — the ABSENCE is how a reader detects it');
$m2 = SWUBotDataReadMeta($dir2);
$check(($m2['finished'] ?? null) === false, 'the reader reports it as unfinished rather than failing; got ' . json_encode($m2));

$wipe();
bot_test_finish();
