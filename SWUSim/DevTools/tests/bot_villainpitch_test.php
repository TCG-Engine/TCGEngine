<?php
// Feature 'villainpitch' (p42) — HMW_010 Tarfful: "Action [2 resources, Exhaust, discard a card from your hand]: Create a Beast token."
// OWNER RULING 2026-10-08 (Tarfful Blue Kashyyyk): "tarfful's action is really only for the T1 play. if you can discard a villainy card
// turn 1, then take that line. it half-activates Anakin [LOF_070: 'If there is a Villainy card in your discard pile, you may give a unit
// -3/-3']. then if you can find a window to play a 2-drop and then a 2 or 3 drop on the 4R or 5R turn respectively and then pay 2 to
// discard a villainy card (if one is not already in the discard) before 6R, then take that line as well. this deck leans into getting
// Anakin online and stabilizing on flip turn."
// So the Action is the PITCH LINE and nothing else: an off-aspect Villainy card in hand, none in the discard yet, an Anakin still to come,
// fewer than 6 resources — round 1 at once, later with the LAST 2 resources once the unit plays are made. Its discard pick is the
// Villainy card (the 'heropitch' mirror). Audit 2026-10-08: the Action took the most expensive unaffordable card as its "unlock" value —
// used in round 1 in 48/48 games and over a 4-drop it then could not cast; its discard kept Chimaera (pitched 1 of 76 times in hand).
// Fixtures (dictionary-checked): HMW_010 Tarfful (Command/Heroism) · HMW_021 Kachirho (Vigilance) · ASH_052 Chimaera (Vigilance/Villainy,
//   the deck's pitch card) · LOF_070 Anakin Skywalker · SOR_095 Battlefield Marine (2) · LAW_133 Lost and Forgotten · JTL_096 Blue Leader (3).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_villainpitch_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-villainpitch') === ['villainpitch'], 'villainpitch is switchable');
$check(in_array('villainpitch', SWUBotFeatureGroups()['p42'] ?? [], true), 'villainpitch is in group p42');

$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Tarfful ready, round $rnd, $res resources (all ready), $hand, $discard; Anakin in the deck unless $anakin is false.
$board = function (int $rnd, int $res, array $hand, array $discard = [], bool $anakin = true) use ($build) {
    $build(function ($b) use ($rnd, $res, $hand, $discard, $anakin) {
        $b->MyLeader('HMW_010', true); $b->MyBase('HMW_021'); $b->FillResourcesForPlayer(1, 'SOR_095', $res); $b->WithCurrentRoundBeing($rnd);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($discard as $c) $b->WithCardInDiscardForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, ($anakin && $k < 3) ? 'LOF_070' : 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$handIdx = function (string $cid): string { foreach (GetHand(1) as $i => $o) if (strval($o->CardID) === $cid) return "myHand-$i"; return ''; };

// A) Round 1, 2 resources, Chimaera + a Battlefield Marine (2): the pitch line beats the 2-drop.
$board(1, 2, ['ASH_052', 'SOR_095']);
$check($pick() === $ability, 'A: round 1 with a Villainy card — Tarfful pitches it; got ' . $pick());
// …and the discard is the Villainy card (Chimaera), not the Lost and Forgotten / Blue Leader beside it.
$board(1, 2, ['LAW_133', 'JTL_096', 'ASH_052']);
$act(1, 10001, $ability);
$check(str_starts_with($botCtx('midrange')['tooltip'], 'Discard_a_card'), 'A fixture: the discard cost is pending; got ' . $botCtx('midrange')['tooltip']);
$check($pick('no-villainpitch') !== $handIdx('ASH_052'), 'A fixture: today Chimaera (the costliest card) is kept');
$check($pick() === $handIdx('ASH_052'), 'A: Chimaera is pitched');

// B) A Villainy card is already in the discard: no pitch line — the Marine is played.
$board(1, 2, ['ASH_052', 'SOR_095'], ['ASH_052']);
$check($pick() !== $ability, 'B: a Villainy card is already in the discard — no Action; got ' . $pick());
// C) No Villainy card in hand: no pitch line. Today the Action fires anyway.
$board(1, 2, ['SOR_095', 'JTL_096']);
$check($pick('no-villainpitch') === $ability, 'C fixture: today the Action fires with nothing to pitch');
$check($pick() !== $ability, 'C: nothing to pitch — no Action; got ' . $pick());
// D) No Anakin left to activate: no pitch line.
$board(1, 2, ['ASH_052', 'SOR_095'], [], false);
$check($pick() !== $ability, 'D: no Anakin to come — no Action; got ' . $pick());

// E) 4 resources (round 4): the 2-drop first, then the pitch with the last 2.
$board(4, 4, ['ASH_052', 'SOR_095']);
$check($pick() === 'myHand-1!FSM!', 'E: at 4 resources the Marine goes first; got ' . $pick());
$act(1, 10002, 'myHand-1!FSM!');
$act(2, 10001, 'myHealth-0!CustomInput!Pass');
$check($pick() === $ability, 'E: then the pitch, with the last 2 resources; got ' . $pick());

// F) 6 resources: the flip turn — no pitch line.
$board(6, 6, ['ASH_052']);
$check($pick() !== $ability, 'F: 6 resources — no Action; got ' . $pick());

bot_test_finish();
