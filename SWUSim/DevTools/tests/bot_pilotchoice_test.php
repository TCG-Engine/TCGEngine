<?php
// Feature 'pilotchoice' (p42) — the pilot leaders' deploy: JTL_006 Darth Vader ("When deployed as an upgrade: Create 2 TIE Fighter
// tokens"), JTL_009 Boba Fett ("When deployed as an upgrade: Deal up to 4 damage divided …"), JTL_012 Luke Skywalker ("Attached unit is
// a leader unit. If it's a Fighter, it gains: 'On Attack: You may deal 3 damage to a unit'").
// Leader audit 2026-10-08: the "Deploy_as_Unit_or_Pilot?" answer was a 0.2 guide (Pilot only with a READY Vehicle) — Boba deployed as a
// plain 4/7 in ~11% of deploys with no ready host, giving up the 4-damage split that needs none; and the "Choose_a_Vehicle_to_deploy_onto"
// pick was never scored, so the FIRST listed Vehicle took the pilot: Vader chose the best host 39/120 times, a token 6, a 1-HP host 11.
// Luke's host was never checked for a Fighter. Fixed: Pilot whenever a usable Vehicle exists — any, for a "When deployed as an upgrade"
// pilot; a READY one otherwise (its value is the host's attack, and a leader unit deploys ready); with the trait, for an "If it's a
// <Trait>" pilot — else Unit. The host is a ready, sturdy Vehicle (with the trait, when the pilot names one).
// Fixtures (dictionary-checked): JTL_006 / JTL_009 / JTL_012 · JTL_020 base · JTL_T01 TIE Fighter token (1/1) · ASH_099 Gozanti
//   (4/6, Capital Ship) · JTL_162 Droid Missile Platform (4/2) · JTL_096 Blue Leader (3/3 Fighter) · SOR_095 (theirs).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_pilotchoice_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-pilotchoice') === ['pilotchoice'], 'pilotchoice is switchable');
$check(in_array('pilotchoice', SWUBotFeatureGroups()['p42'] ?? [], true), 'pilotchoice is in group p42');

$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// $leader undeployed, 6 resources; $space = [cardID, ready] my space units in order; their Marine. Then the deploy is started.
$deploy = function (string $leader, array $space) use ($build, $act, $botCtx) {
    $build(function ($b) use ($leader, $space) {
        $b->MyLeader($leader, true); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
        foreach ($space as [$c, $r]) $b->WithSpaceUnitForPlayer(1, $c, $r);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
    return $botCtx('midrange')['tooltip'];
};
$hostCard = function (string $mz): string { return preg_match('/^mySpaceArena-(\d+)$/', $mz, $m) ? strval(GetZone('mySpaceArena')[intval($m[1])]->CardID ?? '') : ''; };

// A) Boba, his only Vehicle (Gozanti) EXHAUSTED: today Unit (no ready host); fixed, Pilot — the 4-damage split needs no ready host.
$tip = $deploy('JTL_009', [['ASH_099', false]]);
$check($tip === 'Deploy_as_Unit_or_Pilot?', 'A fixture: the Unit/Pilot choice is pending; got ' . $tip);
$check($pick('no-pilotchoice') === 'Unit', 'A fixture: today Boba deploys as a Unit');
$check($pick() === 'Pilot', 'A: Boba pilots the exhausted Gozanti');

// B) Vader, a TIE Fighter token (listed first) and Gozanti, both ready: today the token; fixed, Gozanti.
$deploy('JTL_006', [['JTL_T01', true], ['ASH_099', true]]);
$act(1, 100, 'Pilot');
$tip = $botCtx('midrange')['tooltip'];
$check($tip === 'Choose_a_Vehicle_to_deploy_onto', 'B fixture: the host pick is pending; got ' . $tip);
$check($hostCard($pick('no-pilotchoice')) === 'JTL_T01', 'B fixture: today the first-listed token takes Vader');
$check($hostCard($pick()) === 'ASH_099', 'B: Vader pilots the Gozanti (4/6), not the 1/1 token');
// …a READY host over an exhausted one of the same kind.
$deploy('JTL_006', [['ASH_099', false], ['ASH_099', true]]);
$act(1, 100, 'Pilot');
$check($pick() === 'mySpaceArena-1', 'B: the ready Gozanti, not the exhausted one; got ' . $pick());

// …the sturdier of two equal-power hosts (the pilot is defeated with its host): Droid Missile Platform (4/2, listed first) vs Gozanti (4/6).
$deploy('JTL_006', [['JTL_162', true], ['ASH_099', true]]);
$act(1, 100, 'Pilot');
$check($hostCard($pick()) === 'ASH_099', 'B: the 4/6 Gozanti, not the 4/2 Platform; got ' . $hostCard($pick()));

// C) Luke, Gozanti (listed first, not a Fighter) and Blue Leader (a Fighter): fixed, Blue Leader — he gains "deal 3" only on a Fighter.
$deploy('JTL_012', [['ASH_099', true], ['JTL_096', true]]);
$check($pick() === 'Pilot', 'C: Luke with a Fighter available — Pilot');
$act(1, 100, 'Pilot');
$check($hostCard($pick()) === 'JTL_096', 'C: Luke pilots the Fighter (Blue Leader); got ' . $hostCard($pick()));
// D) Luke with only an EXHAUSTED Fighter: Unit — his value is the host's attack, and as a unit he deploys ready and swings now.
$deploy('JTL_012', [['JTL_096', false]]);
$check($pick() === 'Unit', 'D: Luke, only an exhausted Fighter — Unit; got ' . $pick());
// D) Luke with only a Gozanti (no Fighter): Unit.
$deploy('JTL_012', [['ASH_099', true]]);
$check($pick() === 'Unit', 'D: Luke with no Fighter to pilot — Unit; got ' . $pick());

// E) A recorded lookahead plan does not override it. The audit's 'planned-answer' rule (a plan the lookahead stored when it chose the
// deploy) answered Unit 140 times with a ready Vehicle out. Seed that plan as BotLookahead stores it — Boba, a ready Gozanti.
$deploy('JTL_009', [['ASH_099', true]]);
$GLOBALS['SWUBotPlan'][1] = [['tooltip' => 'Deploy_as_Unit_or_Pilot?', 'answer' => 'Unit']];
$check($pick('no-pilotchoice') === 'Unit', 'E fixture: today the planned Unit answer is followed');
$GLOBALS['SWUBotPlan'][1] = [['tooltip' => 'Deploy_as_Unit_or_Pilot?', 'answer' => 'Unit']];
$got = $pick();
$check($got === 'Pilot', 'E: the pilot choice overrides a planned Unit answer; got ' . $got);

bot_test_finish();
