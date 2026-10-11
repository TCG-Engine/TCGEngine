<?php
// Feature 'unitedge' (p42) — HMW_008 General Grievous, deployed: "While you control more units than an opponent, this unit gets +3/+0."
// Leader audit 2026-10-08: the bot reads his power live, so the +3 is seen when it is on — but 7 of his attacks went in at an EVEN unit
// count while an affordable unit in hand would have switched the +3 on first. Fixed: while a unit play would give me the edge, that play
// goes before his attack.
// Fixtures (dictionary-checked): HMW_008 Grievous · HMW_021 Kachirho · LOF_084 Knight of Ren (3) · SOR_095 Battlefield Marine (theirs).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_unitedge_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-unitedge') === ['unitedge'], 'unitedge is switchable');
$check(in_array('unitedge', SWUBotFeatureGroups()['p42'] ?? [], true), 'unitedge is in group p42');

$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Deployed Grievous (ready, alone), 3 ready resources, a Knight of Ren (3) in hand, $theirs exhausted Marines.
$board = function (int $theirs) use ($build) {
    $build(function ($b) use ($theirs) {
        $b->MyLeader('HMW_008', true, true, true, 'unit'); $b->MyBase('HMW_021'); $b->FillResourcesForPlayer(1, 'LOF_084', 3); $b->WithCurrentRoundBeing(6);
        $b->WithCardInHandForPlayer(1, 'LOF_084');
        for ($k = 0; $k < $theirs; $k++) $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 10; $k++) { $b->WithCardInDeckForPlayer(1, 'LOF_084'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$isAttack = fn(string $p) => (bool)preg_match('/^myGroundArena-\d+!FSM!$/', $p);

// A) 1 unit each: the Knight makes it 2 v 1 and Grievous attacks as an 6/6. Today he attacks first as a 3/6.
$board(1);
$check($isAttack($pick('no-unitedge')), 'A fixture: today Grievous attacks at an even count; got ' . $pick('no-unitedge'));
$check($pick() === 'myHand-0!FSM!', 'A: the Knight goes first to switch on the +3; got ' . $pick());

// B) Their 2 units: 2 v 2 even after the Knight — no edge to win, the order is unchanged.
$board(2);
$check($pick() === $pick('no-unitedge'), 'B: the play would not give the edge — unchanged');
// …and the attack is not held for it. (Behind on bodies, 'blockerfirst' already plays the Knight first, so the pick above cannot tell;
// the hold itself is read off the attack's own helper.)
$c = $botCtx('midrange'); $gv = null;
foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'HMW_008') $gv = $v;
$check($gv !== null && _SWUBotUnitEdgePlay($c, $gv) === null, 'B: 2 v 1 — the Knight would not give the edge, so the attack is not held');

// C) No enemy unit: 1 v 0, the +3 is already on — he attacks first, as today.
$board(0);
$check($isAttack($pick()), 'C: already ahead — Grievous attacks first; got ' . $pick());

bot_test_finish();
