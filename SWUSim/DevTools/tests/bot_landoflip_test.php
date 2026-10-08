<?php
// Features 'landomill' (SHIPPED 2026-10-08, was a proposal) + 'landoflip' (p42) — LAW_018 Lando Calrissian, Full Sabacc. OWNER RULINGS
// 2026-10-08 (Lando Blue):
//   - "turn it on" ('landomill', the 2026-09-23 line: mill MY deck for the guaranteed Credit; after the flip, mill THEIRS on a spare
//     resource). "the general rule is that Lando aims for 9 for Bo-Katan and off-aspect Chimaera … they also go higher if they need more
//     resources in a grindy game. so no guaranteed Credits are needed when Lando has 10+ resources. 9 + 1 for the mill ability" — so
//     THEIR deck is milled after the flip OR at 10+ resources; milling theirs, name their leader's color (never Heroism/Villainy).
//   - "before Lando flips, make sure you have 1 Credit to triple it. do not deploy Lando with 0 Credits" (When Deployed: "defeat a
//     friendly Credit token. If you do, create 3 Credit tokens").
//   - "the ability may not be necessary if you already had one banked from the 5R turn. this way you can play a 9-drop. if you have 0
//     Credits on 5R, then on 6R use the ability, you will only have 5R + 3C available, enough for a 8-drop but no Bo-Katan or Chim" —
//     so with a Credit banked the 6R Action is skipped, and the banked Credit is not spent before the flip.
// Fixtures (dictionary-checked): LAW_018 Lando · ASH_019 base · LAW_T01 Credit · JTL_009 Boba Fett (Aggression/Villainy) · ASH_053 /
//   LAW_133 / SEC_078 (Vigilance — my deck) · LOF_070 Anakin Skywalker (6, Vigilance).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_landoflip_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['landomill', 'landoflip'] as $f) {
    $check(SWUBotVariantDisabled("no-$f") === [$f], "$f is switchable");
    $check(in_array($f, SWUBotFeatureGroups()['p42'] ?? [], true), "$f is in group p42");
}
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$scoreOf = function (string $id, array $off = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($off); $c = $botCtx('softcontrol'); $s = null;
    foreach ($c['actions'] as $i => $a) if (strval($a['cardID']) === $id) $s = SWUBotScoreAction($c, $a, $i);
    SWUBotSetDisabledFeatures([]); return $s;
};
// Lando (undeployed, Epic Action unspent unless $flipped), $res ready resources + $credits Credits, $hand; vs Boba Fett (JTL).
$lando = function (int $res, int $credits = 0, array $hand = [], bool $flipped = false) use ($build) {
    $build(function ($b) use ($res, $credits, $hand, $flipped) {
        $b->MyLeader('LAW_018', true, false, $flipped); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        if ($credits > 0) $b->FillResourcesForPlayer(1, 'LAW_T01', $credits);
        $b->WithCurrentRoundBeing(max(1, $res));
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 10; $k++) foreach (['ASH_053', 'LAW_133', 'SEC_078'] as $c) $b->WithCardInDeckForPlayer(1, $c);
        $b->TheirLeader('JTL_009', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) $b->WithCardInDeckForPlayer(2, 'SOR_095');
    });
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$deploy = 'myLeader-0!CustomInput!DeployLeader:Unit';
$mill = function () use ($act, $pick) { $act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility'); $a = $pick(); $act(1, 100, $a); return [$a, $pick()]; };

// L) The deck milled — shipped ON (no variant): 9 resources pre-flip → my deck, Vigilance; 10 → theirs, Boba's color (Aggression).
$lando(9);
[$asp, $deck] = $mill();
$check($asp === 'Vigilance' && $deck === 'Your_deck', 'L: 9 resources — my deck, Vigilance; got ' . json_encode([$asp, $deck]));
$lando(10);
[$asp, $deck] = $mill();
$check($asp === 'Aggression' && $deck === "Opponent's_deck", 'L: 10 resources — theirs, his color (Aggression); got ' . json_encode([$asp, $deck]));
$lando(4, 0, [], true);
[$asp, $deck] = $mill();
$check($deck === "Opponent's_deck", 'L: after the flip (4 resources) — theirs, as ruled 2026-09-23; got ' . json_encode([$asp, $deck]));

// F1) 6 resources, 0 Credits: never deploy — the Action (a guaranteed Credit) first. Today the deploy is ready to go at 0 Credits.
$lando(6);
$check($scoreOf($deploy) < 0, 'F1: 0 Credits — the deploy waits; got ' . $scoreOf($deploy));
$check($scoreOf($deploy, ['landoflip']) > 0, 'F1 fixture: today the deploy scores to go');
$check($pick() === $ability, 'F1: the Action first; got ' . $pick());
// F2) 6 resources, 1 Credit banked (from 5R): no Action — deploy (the Credit triples: 6R + 3C = 9 for Bo-Katan). Today the Action fires.
$lando(6, 1);
$check($scoreOf($ability, ['landoflip']) > 0, 'F2 fixture: today the Action scores to go');
$check($scoreOf($ability) <= 0, 'F2: a Credit is banked — the Action is not needed; got ' . $scoreOf($ability));
$check($pick() === $deploy, 'F2: Lando deploys; got ' . $pick());
// F4) Before the flip (5 resources + 1 Credit), an Anakin (6) would spend the banked Credit — held. Today it is played.
$lando(5, 1, ['LOF_070']);
$check($pick('no-landoflip') === 'myHand-0!FSM!', 'F4 fixture: today Anakin spends the banked Credit');
$check($pick() !== 'myHand-0!FSM!', 'F4: the last Credit is kept for the flip; got ' . $pick());

// F5) …but only once the flip is on offer: at 5 resources (no deploy yet) with a Credit banked, the Action still ramps.
$lando(5, 1);
$check($scoreOf($ability) > 0, 'F5: before the flip round the Action still ramps; got ' . $scoreOf($ability));

bot_test_finish();
