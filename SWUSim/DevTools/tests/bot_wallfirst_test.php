<?php
// Feature 'wallfirst' (p39) — control vs an aggro leader puts its Sentinel down FIRST in the early rounds. Owner, Krennic (LAW) Blue
// questionnaire 2026-10-06: vs aggro the deck stabilises with "Sentinel wall + trades"; the opener is "hold for R2 Sentinel" — R1 cheap
// fodder → a Credit, "R2 you should have 3 resources. so you'd be able to pay for Gideon regardless". (Splash, same day: R2-4 vs Ahsoka
// "Sentinels down + trade into her units".)
// Traced (480 Krennic Blue games, baseline kb1): with Moff Gideon or Imperial Armored Commando in hand going into R2, one was played on
// R2 in only 16 of 38 aggro games; the most common R2 play was Latts Razzi (121).
//   · rounds 1-4, control style, vs an aggro leader, free play, no friendly Sentinel yet in that arena: play the best castable Sentinel
//     (printed) the fallback wants (score > 0 — its holds stand, as for 'blockerfirst').
// Fixtures (dictionary-checked): LAW_008 Director Krennic · ASH_019 · ASH_097 Moff Gideon (3, Sentinel) · LAW_039 Latts Razzi (3) ·
//   ASH_009 Ahsoka Tano + SOR_030 (Ahsoka Yellow) · HMW_003 Doctor Hemlock (not aggro) · SOR_095 · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wallfirst_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-wallfirst') === ['wallfirst'], 'wallfirst is switchable');
$check(in_array('wallfirst', SWUBotFeatureGroups()['p39'] ?? [], true), 'wallfirst is in group p39');
// Round $rnd, $rnd+1 resources, hand Gideon + Latts; my $mine; their $leader with a Marine. The card the bot plays (its CardID).
$pick = function (int $rnd, string $leader, array $mine, string $variant) use ($build, &$gameName) {
    $build(function ($b) use ($rnd, $leader, $mine) {
        $b->MyLeader('LAW_008', false); $b->MyBase('ASH_019'); $b->TheirLeader($leader, false); $b->TheirBase('SOR_030');
        $b->WithCurrentRoundBeing($rnd); $b->FillResourcesForPlayer(1, 'LAW_097', $rnd + 1);
        foreach (['LAW_039', 'ASH_097'] as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    });
    SWUBotResetCoverage();
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    $mz = strval($p['cardID'] ?? '');
    $o = preg_match('/^myHand-(\d+)/', $mz, $m) ? (GetHand(1)[intval($m[1])] ?? null) : null;
    return $o !== null ? strval($o->CardID) : $mz;
};
// A) R2 vs Ahsoka Yellow: today Latts Razzi; fixed, Moff Gideon.
$check($pick(2, 'ASH_009', [], 'no-wallfirst') === 'LAW_039', 'A fixture: today R2 is Latts Razzi; got ' . $pick(2, 'ASH_009', [], 'no-wallfirst'));
$check($pick(2, 'ASH_009', [], '') === 'ASH_097', 'A: R2 is Moff Gideon; got ' . $pick(2, 'ASH_009', [], ''));
// B) A Sentinel (Imperial Armored Commando) already guards the ground: unchanged.
$check($pick(2, 'ASH_009', ['ASH_048'], '') === $pick(2, 'ASH_009', ['ASH_048'], 'no-wallfirst') && $pick(2, 'ASH_009', ['ASH_048'], '') !== 'ASH_097', 'B: an Imperial Armored Commando already guards the ground — unchanged (no Gideon); got ' . $pick(2, 'ASH_009', ['ASH_048'], ''));
// C) Not an aggro leader (Hemlock): unchanged.
$check($pick(2, 'HMW_003', [], '') === $pick(2, 'HMW_003', [], 'no-wallfirst'), 'C: vs a non-aggro leader — unchanged');
// D) Round 5: past the early rounds — unchanged.
$check($pick(5, 'ASH_009', [], '') === $pick(5, 'ASH_009', [], 'no-wallfirst'), 'D: round 5 — unchanged');

bot_test_finish();
