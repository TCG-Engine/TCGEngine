<?php
// Feature 'attackreserve' (p42) — a ready unit with "On Attack: You may pay N resource(s). If you do, create a <X> token" (HMW_010 Tarfful,
// deployed: "…pay 1 resource. If you do, create a Beast token"). Leader audit 2026-10-08: 27 of 37 deployed Tarfful attacks went in with 0
// ready resources (32 in the deploy round), and the On Attack was logged "had no effect". OWNER (2026-10-08): "on 7R, you can play Anakin
// plus Tarfful swing + 1R for beast token … evaluate whether spending resources or saving 1 for a Beast token On Attack is worth more."
// Fixed: while such a unit can still attack, a play that would leave fewer than N resources is charged the token it gives up (priced as
// a unit play of the token's size). A play worth more than that still goes; one that leaves N is untouched.
// Fixtures (dictionary-checked): HMW_010 Tarfful · HMW_021 Kachirho · ASH_109 T-6 Shuttle 1974 (4) · JTL_096 Blue Leader (3)
//   · LOF_070 Anakin Skywalker (6) · ASH_052 / JTL_096 in the discard (both Anakin halves live) · SOR_164 Wampa / SOR_095 (theirs).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_attackreserve_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-attackreserve') === ['attackreserve'], 'attackreserve is switchable');
$check(in_array('attackreserve', SWUBotFeatureGroups()['p42'] ?? [], true), 'attackreserve is in group p42');

$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Deployed Tarfful ($ready = can still attack), $res ready resources, $hand; both Anakin halves live; their Wampa + Marine.
$board = function (int $res, array $hand, bool $ready = true) use ($build) {
    $build(function ($b) use ($res, $hand, $ready) {
        $b->MyLeader('HMW_010', $ready, true, true, 'unit'); $b->MyBase('HMW_021'); $b->FillResourcesForPlayer(1, 'SOR_095', $res); $b->WithCurrentRoundBeing(7);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithCardInDiscardForPlayer(1, 'ASH_052'); $b->WithCardInDiscardForPlayer(1, 'JTL_096');
        $b->WithGroundUnitForPlayer(2, 'SOR_164', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$tarffulReady = function (): bool { foreach (GetZone('myGroundArena') as $o) if (strval($o->CardID) === 'HMW_010') return intval($o->Status) === 1; return false; };

// A) 4 resources, T-6 Shuttle (4) and Blue Leader (3): today T-6 spends all 4 and the Beast is lost. Fixed: not T-6 (Blue Leader keeps 1,
// or Tarfful swings first and pays it).
$board(4, ['ASH_109', 'JTL_096']);
$check($tarffulReady(), 'A fixture: deployed Tarfful is ready');
$check($pick('no-attackreserve') === 'myHand-0!FSM!', 'A fixture: today T-6 spends the last resource');
$check($pick() !== 'myHand-0!FSM!', 'A: T-6 would leave nothing for the Beast — it is not the play; got ' . $pick());
// …and Blue Leader, which leaves the 1, is not charged at all (its score is the same with the feature off); T-6 is.
$score = function (string $cid) use ($botCtx) { $c = $botCtx('midrange'); foreach ($c['actions'] as $i => $a) if ($a['cardID'] === $cid) return SWUBotScoreAction($c, $a, $i); return null; };
$on = [$score('myHand-0!FSM!'), $score('myHand-1!FSM!')];
SWUBotSetDisabledFeatures(['attackreserve']); $off = [$score('myHand-0!FSM!'), $score('myHand-1!FSM!')]; SWUBotSetDisabledFeatures([]);
$check(abs($on[1] - $off[1]) < 1e-9, 'A: Blue Leader (leaves 1) is not charged; got ' . json_encode([$on[1], $off[1]]));
$W = SWUBotWeights('midrange', 1); $beast = $W['develop'] * (intval(CardPower('HMW_T03')) + intval(CardHp('HMW_T03'))) / 2 + $W['unitPlay'];
$check(abs(($off[0] - $on[0]) - $beast) < 1e-9, 'A: T-6 (leaves 0) is charged exactly the Beast, priced as a 3/3 unit play; got ' . json_encode([$on[0], $off[0], $beast]));
// B) Tarfful has already attacked (exhausted): nothing to save for — T-6, as today.
$board(4, ['ASH_109', 'JTL_096'], false);
$check($pick() === 'myHand-0!FSM!', 'B: Tarfful exhausted — T-6, unchanged; got ' . $pick());
// C) The owner's 7R: 7 resources, Anakin (6, both halves live) leaves 1 for the Beast — Anakin, as today.
$board(7, ['LOF_070']);
$check($pick() === 'myHand-0!FSM!', 'C: 7 resources — Anakin, with 1 left for the Beast; got ' . $pick());
// D) 6 resources, Anakin: he spends the Beast's resource, but two -3/-3s are worth more than a 3/3 — still Anakin.
$board(6, ['LOF_070']);
$check($pick() === 'myHand-0!FSM!', 'D: 6 resources — Anakin is worth more than the Beast; got ' . $pick());

bot_test_finish();
