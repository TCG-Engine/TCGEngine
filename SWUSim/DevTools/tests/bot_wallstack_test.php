<?php
// Feature 'wallstack' (p42) — OWNER PLAN 2026-10-09, Mando Colossus vs aggro: "Mando's plan against aggro is sentinel up most turns.
// T1 claim/draw · T2 Loth wolf · T3 another Loth wolf or droid laser turret · T4 same as above maybe Let's Call it War to finish off units
// that ran into sentinels. ideally Zeb for 5R to ping off a unit and then block more hits". (Built as rules; the fixture list stays.)
// 'wallfirst' (p39, Krennic Blue) plays a Sentinel first in rounds 1-4 vs an aggro leader — but only while that arena has NO Sentinel of
// mine, and it fires in round 1 too. For HARD CONTROL now:
//   - rounds 1-4: keep putting Sentinels down even with one already up ("sentinel up most turns").
// ("T1 claim/draw" already holds: 'mandoclaim' charges a round-1 play the draw it spends, so the wall does not jump the claim — B pins it.)
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · LOF_044 Loth-Wolf (2, 3/3 Sentinel, can't attack) · LAW_118
//   Droid Laser Turret (3, Sentinel) · LAW_049 Bith Brute (3, Sentinel) · ASH_031 Hera Syndulla (3) · SEC_148 Karis Nemik (2) · ASH_009 Ahsoka (aggro leader) ·
//   LAW_008 Krennic (not aggro) · JTL_006 Vader (aggro, space) · SOR_225 TIE/ln · ASH_097 Moff Gideon (3, Sentinel) · ASH_048 Imperial Armored Commando (4, Sentinel) · LAW_039 Latts Razzi (3) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wallstack_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-wallstack') === ['wallstack'], 'wallstack is switchable');
$check(in_array('wallstack', SWUBotFeatureGroups()['p42'] ?? [], true), 'wallstack is in group p42');
$pick = function (string $variant = '', string $style = 'hardcontrol') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$handCard = fn(string $mz) => preg_match('/^myHand-(\d+)/', $mz, $m) ? strval(GetHand(1)[intval($m[1])]->CardID ?? '') : $mz;
$claim = 'InitiativeCounter-0!CustomInput!TakeInitiative';
// $leader on round $rnd with $res resources, $mine (Sentinels already up, exhausted), $hand; their $lead with a Karis on the ground.
$board = function (string $leader, int $rnd, int $res, array $mine, array $hand, string $lead = 'ASH_009') use ($build) {
    $build(function ($b) use ($leader, $rnd, $res, $mine, $hand, $lead) {
        $b->MyLeader($leader, true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', $res); $b->WithCurrentRoundBeing($rnd);
        $b->TheirLeader($lead); $b->WithGroundUnitForPlayer(2, 'SEC_148', false);
        foreach ($mine as $u) $b->WithGroundUnitForPlayer(1, $u, false);   // already up, exhausted (it still blocks)
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};

// A) Round 3, 4 resources, a Bith Brute (Sentinel) already up, Turret + Hera in hand vs Ahsoka: today Hera; another Sentinel goes down.
$board('ASH_014', 3, 4, ['LAW_049'], ['ASH_031', 'LAW_118']);
$check($handCard($pick('no-wallstack')) !== 'LAW_118', 'A fixture: today the second Sentinel is not the play; got ' . $handCard($pick('no-wallstack')));
$check($handCard($pick()) === 'LAW_118', 'A: stack the Droid Laser Turret — Sentinel up most turns; got ' . $handCard($pick()));

// B) Round 1, 2 resources, Loth-Wolf in hand vs Ahsoka: "T1 claim/draw" — the claim, not the wall (guard).
$board('ASH_014', 1, 2, [], ['LOF_044']);
$check($pick() === $claim, 'B: round 1 — claim and draw first; got ' . $pick());
// B2) Round 2 (3 resources): the Loth-Wolf goes down ("T2 Loth wolf").
$board('ASH_014', 2, 3, [], ['LOF_044']);
$check($handCard($pick()) === 'LOF_044', 'B2: round 2 — the Loth-Wolf; got ' . $handCard($pick()));

// C) Not an aggro leader (Krennic): A's board is unchanged.
$board('ASH_014', 3, 4, ['LAW_049'], ['SEC_148', 'LAW_118'], 'LAW_008');
$check($pick() === $pick('no-wallstack'), 'C: vs a non-aggro leader — unchanged; got ' . json_encode([$pick(), $pick('no-wallstack')]));

// D) Soft control (Krennic Blue's wall-first, one Sentinel per arena) does not stack: a Moff Gideon up, Latts Razzi + an Imperial
//    Armored Commando (non-unique Sentinel) in hand on 6 resources — Latts, as before; the hard-control rule must not reach it.
$board('LAW_008', 3, 6, ['ASH_097'], ['LAW_039', 'ASH_048']);
$check($handCard($pick('', 'softcontrol')) === 'LAW_039', 'D: soft control does not stack the Commando — Latts; got ' . $handCard($pick('', 'softcontrol')));

// E) Round 5: past the early rounds — A's board is the ordinary scorer's call again.
$board('ASH_014', 5, 6, ['LAW_049'], ['SEC_148', 'LAW_118']);
$check($pick() === $pick('no-wallstack'), 'E: round 5 — unchanged; got ' . json_encode([$pick(), $pick('no-wallstack')]));

// F) Against SPACE (owner: "against space, early let's call it wars, direct hit on 5R … survive enough to use HSD"): stacking only walls an
//    arena the opponent is IN. Vader (JTL) with two ships and nothing on the ground: no second ground Sentinel — unchanged.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
    $b->TheirLeader('JTL_006'); $b->WithSpaceUnitForPlayer(2, 'SOR_225', false); $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
    $b->WithGroundUnitForPlayer(1, 'LAW_049', false); $b->WithCardInHandForPlayer(1, 'ASH_031'); $b->WithCardInHandForPlayer(1, 'LAW_118');
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick() === $pick('no-wallstack'), 'F: vs space only — no ground wall stacked; got ' . json_encode([$pick(), $pick('no-wallstack')]));

bot_test_finish();
