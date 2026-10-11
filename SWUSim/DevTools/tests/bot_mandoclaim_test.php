<?php
// Feature 'mandoclaim' (p42) — ASH_014 The Mandalorian, We Can't Keep Running: "When you take the initiative: You may pay 1 resource. If you
// do, draw a card." OWNER PLAN 2026-10-08 (Mando Colossus, docs/superpowers/research/2026-09-premier-meta/2026-10-08_deck_mando-colossus.md):
// "2R/T1 immediately claim and draw · 3R play a 2-drop then claim and draw · 4R play a 3-drop then claim and draw". Leader audit 2026-10-08:
// the initiative was worth 0.05 and claimed only when nothing else was left — 43% of claims had 0 resources for the draw.
// Taking the initiative ENDS my actions for the round, so the order is: the plays that still leave the resource first, then the claim with
// that resource. Fixed: while the claim's draw is on offer (the initiative unclaimed, the leader undeployed), the claim is worth the draw —
// kept just under any play that leaves the resource — and a play that would spend that last resource is charged the draw.
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · SEC_148 Karis Nemik (2, Aggression/Heroism) · JTL_212 Republic Y-Wing (1/3) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_mandoclaim_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-mandoclaim') === ['mandoclaim'], 'mandoclaim is switchable');
$check(in_array('mandoclaim', SWUBotFeatureGroups()['p42'] ?? [], true), 'mandoclaim is in group p42');
$pick = function (string $variant = '', string $style = 'hardcontrol') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$claim = 'InitiativeCounter-0!CustomInput!TakeInitiative';
// Mando (front), round $rnd with $res ready resources, $hand; their exhausted Marine.
$board = function (int $rnd, int $res, array $hand, bool $theyClaimed = false, bool $attacker = false) use ($build) {
    $build(function ($b) use ($rnd, $res, $hand, $theyClaimed, $attacker) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', $res); $b->WithCurrentRoundBeing($rnd);
        if ($theyClaimed) { $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); }
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
        if ($attacker) $b->WithSpaceUnitForPlayer(1, 'JTL_212', true);   // Republic Y-Wing 1/3: a small swing, below the draw
    });
};

// A) 2R (T1): 2 resources, a 2-drop in hand — "immediately claim and draw" (the 2-drop would spend the draw's resource). Today the 2-drop.
$board(1, 2, ['SEC_148']);
$check($pick('no-mandoclaim') === 'myHand-0!FSM!', 'A fixture: today the 2-drop; got ' . $pick('no-mandoclaim'));
$check($pick() === $claim, 'A: claim the initiative (and draw); got ' . $pick());

// A2) The same on lighter draw weights (midrange, a draw 0.5 — the 2-drop outscores a bare draw): both halves are needed — the 2-drop
// charged the draw it spends, AND the claim worth that draw.
$board(1, 2, ['SEC_148']);
$check($pick('', 'midrange') === $claim, 'A2: midrange weights — still claim and draw; got ' . $pick('', 'midrange'));

// B) 3R: 3 resources, the same 2-drop — it leaves 1: the 2-drop FIRST (the claim ends my actions), then the claim with the last resource.
$board(2, 3, ['SEC_148']);
$check($pick() === 'myHand-0!FSM!', 'B: the 2-drop first; got ' . $pick());
$act(1, 10002, 'myHand-0!FSM!');
for ($k = 0; $k < 3 && $botCtx('hardcontrol')['kind'] === 'decision'; $k++) $act(1, 100, 'PASS');
$act(2, 10001, 'myHealth-0!CustomInput!Pass');
$check($pick() === $claim, 'B: then the claim, with 1 resource for the draw; got ' . $pick());

// C) The opponent already claimed the initiative: no draw on offer — the 2-drop is played, uncharged.
$board(1, 2, ['SEC_148'], true);
$check($pick() === 'myHand-0!FSM!', 'C: the initiative is gone — the 2-drop; got ' . $pick());

// D) A ready Republic Y-Wing (1 power) with a swing at the base, and a 2-drop on 2 resources (it would spend the draw's resource): even a
// small attack goes first — the claim would end my actions — then the claim. (The shipped 'attack-first' guide and rule 8 "no unused
// attacks" are what order it; this pins that the claim's new value does not jump ahead of them.)
$board(2, 2, ['SEC_148'], false, true);
$check(preg_match('/^mySpaceArena-\d+!FSM!$/', $pick()) === 1, 'D: the attack before the claim; got ' . $pick());

bot_test_finish();
