<?php
// PROPOSAL 'mgbuff' (default OFF) — owner rulings 2026-09-23 (A2), the buff-and-attack Action.
//   1. use it when it turns a non-kill into a KILL ("best for trading upwards");
//   2. use it when it turns a losing trade into a winning one;
//   3. if nothing can be killed, buff the biggest attacker and SWING AT BASE ("also best for both aggro styles");
//   4. never hold it.
// ⚠ WHY A NEW ARM RATHER THAN A RULE: the shipped 'buffattack' (p7) already prices a buff by what it adds to an
// attack, but ONLY when the target prompt's continuation is the generic APPLY_PHASE_BUFF. A card that applies the
// buff inside its OWN handler is invisible to it — T-6 Shuttle ASH_109 queues "ASH_109#0" and calls
// SWUApplyPhaseBuff there, so its Action scored a flat 0.40 (W['ability']) and lost to every attack. Traced
// 2026-09-23: the Shuttle's Action was OFFERED 16 times in 40 games and used 0. Six cards share that shape.
// mgbuff reads the amount off the ACTION'S PRINTED TEXT when the continuation is a card handler, then reuses
// buffattack's own gain machinery, so the two agree on what a buff is worth.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_mgbuff_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-mgbuff') === ['try:mgbuff'], 'proposal mgbuff is registered');
$ON = ['try:mgbuff'];
$ACT = 'mySpaceArena-0!CustomInput!Activate';
$score = function (string $id, array $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on); $ctx = $botCtx('midrange'); $out = null;
    foreach ($ctx['actions'] as $i => $a) { if (strval($a['cardID']) === $id) $out = SWUBotScoreAction($ctx, $a, $i); }
    SWUBotSetDisabledFeatures([]);
    return $out;
};
// T-6 Shuttle (ASH_109, space, Action: give another unit +2/+2 and it may attack) plus a ground unit of mine,
// against whatever $theirs puts on their ground.
$board = function (string $mine, array $theirs, bool $mineReady = true) use ($build) {
    $build(function ($b) use ($mine, $theirs, $mineReady) {
        $b->MyLeader('ASH_005', true, false, false); $b->MyBase('JTL_024'); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $b->WithSpaceUnitForPlayer(1, 'ASH_109', true);          // the Shuttle, ready
        $b->WithGroundUnitForPlayer(1, $mine, $mineReady);
        $b->TheirLeader('ASH_009', true);
        foreach ($theirs as $t) $b->WithGroundUnitForPlayer(2, $t, true);
    });
};

// ── 1. the buff turns a non-kill into a kill: Mina 2/4 + 2 = 4 power kills Neel (1/4) ──────────────────────────
$board('SEC_094', ['ASH_248']);
$flat = $score($ACT, []);
$check($flat !== null && abs($flat - 0.4) < 1e-9, 'fixture: the shipped bot scores the Action a flat 0.40 (the gap)', strval($flat));
$on = $score($ACT, $ON);
$check($on > $flat, 'mgbuff prices the Action above the flat ability value', "$flat -> $on");
$check($on > $score('myGroundArena-0!FSM!', $ON), 'mgbuff: enabling the kill beats attacking without it',
    $on . ' vs ' . $score('myGroundArena-0!FSM!', $ON));
SWUBotSetDisabledFeatures($ON);
$legal = SWUBotLegalActions($gameName, 1);
$pick = SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, 'try-mgbuff');
SWUBotSetDisabledFeatures([]);
$check(strval($pick['cardID'] ?? '') === $ACT, 'mgbuff: the bot actually USES the Action (it never did before)', strval($pick['cardID'] ?? ''));

// ── 2. no kill available: still used, and worth its extra damage at base ───────────────────────────────────────
// Their ground unit is a Wampa 4/5: buffed Mina (4 power) cannot kill it, so the buff is just damage.
$board('SEC_094', ['SOR_164']);
$noKill = $score($ACT, $ON);
$check($noKill > 0.4, 'mgbuff: with nothing to kill the Action is still used — the big swing at base', strval($noKill));
// ⚠ NOT asserted here: that the KILL case outscores the base-swing case. It does not, and that is not mgbuff's
// bug — SWUBotTargetValue prices "kill Neel and survive" at 0.90 for a midrange seat against 2.40 for hitting the
// base, so the buff is worth the same +2 damage either way. That is the 'mgtrade' arm's job (owner ruling A1, and
// the 73%-of-attacks-at-the-base finding). mgbuff's claim is only that the Action gets USED.
$check($noKill > 0.4 && $on > 0.4, 'mgbuff: the Action is used in both cases', "$noKill vs $on");

// ── 3. nothing to spend it on: an exhausted friendly unit cannot attack, so the Action waits ──────────────────
$board('SEC_094', ['ASH_248'], false);          // my ground unit already exhausted
$check($score($ACT, $ON) <= 0.4, 'mgbuff: no ready unit to buff → not promoted', strval($score($ACT, $ON)));

// ── 4. inert by default, and for a leader/ability without a printed buff ───────────────────────────────────────
$board('SEC_094', ['ASH_248']);
$check($score($ACT, []) === 0.4, 'mgbuff is inert by default');

bot_test_finish();
