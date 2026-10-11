<?php
// LAW_008 Director Krennic's Credit Action ("Action [Exhaust, defeat a friendly unit]: Create a Credit token") — WHICH unit goes.
// OWNER RULINGS 2026-10-08 (closing the leader audit's "Krennic JTL_032 sac" item; the held 'krennicsac' levers are deleted):
//   1. "Latts Razzi when shielded was picked before and then she loses her shield would be the choice over bare Krennic due to stats and
//      value. Krennic is a 2/2 with discount passive. Razzi with no shield is just a 2/1 weenie. but bare Krennic is the choice over a
//      shielded Latts … Koska has good stats even if she is not an active Sentinel." — order: bare Latts < bare Krennic (JTL_032) <
//      Shielded Latts; Koska Reeves kept over bare Krennic. (Already the bot's order — pinned here.)
//   2. Feature 'sentinelsac' (p42): "generally, do not sac active Sentinels. so Gideon or Koska when a token unit is present" — an ACTIVE
//      Sentinel (printed, or gained: ASH_079 Koska "While you control a token unit, this unit gains Sentinel") is the last unit sacrificed;
//      a doomed one (it dies anyway) is not protected. Moff Gideon's When Defeated payback priced him as fodder: he went before a bare
//      Krennic unit and before a 3/3 Battlefield Marine.
// Fixtures (dictionary-checked): LAW_008 Director Krennic (leader) · ASH_019 · JTL_032 Director Krennic (unit 2/2) · LAW_039 Latts Razzi
//   (2/1) · ASH_079 Koska Reeves (4/4) · ASH_097 Moff Gideon (2/5 Sentinel) · ASH_048 Imperial Armored Commando (4/3 Sentinel) · SEC_T01 Spy
//   · ASH_116 Ant Droid · SOR_095 Battlefield Marine (3/3) · SOR_T02 Shield token.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_krennicfodder_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-sentinelsac') === ['sentinelsac'], 'sentinelsac is switchable');
$check(in_array('sentinelsac', SWUBotFeatureGroups()['p42'] ?? [], true), 'sentinelsac is in group p42');
// Krennic's Credit Action raised with my (exhausted) units [cardID, shielded?]; nothing of theirs (no unit is doomed). Returns the CardID
// the bot sacrifices.
$sac = function (array $units, string $variant = '') use ($build, $act, &$gameName) {
    $build(function ($b) use ($units) {
        $b->MyLeader('LAW_008', true); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
        foreach ($units as $k => [$c, $s]) {
            $b->WithGroundUnitForPlayer(1, $c, false);
            if ($s) $b->WithUpgradesOnGroundUnitForPlayer(1, $k, [GameStateBuilder::Upgrade('SOR_T02', 1)]);
        }
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval((@GetZoneObject(strval($p['cardID'] ?? '')))->CardID ?? '');
};

// 1) The owner's order (both listing orders, so no first-listed tiebreak decides it).
$check($sac([['JTL_032', 0], ['LAW_039', 0]]) === 'LAW_039' && $sac([['LAW_039', 0], ['JTL_032', 0]]) === 'LAW_039', '1: a bare Latts (2/1) goes before bare Krennic');
$check($sac([['JTL_032', 0], ['LAW_039', 1]]) === 'JTL_032' && $sac([['LAW_039', 1], ['JTL_032', 0]]) === 'JTL_032', '1: bare Krennic goes before a SHIELDED Latts');
$check($sac([['JTL_032', 0], ['ASH_079', 0]]) === 'JTL_032' && $sac([['ASH_079', 0], ['JTL_032', 0]]) === 'JTL_032', '1: bare Krennic goes before Koska (good stats)');

// 2) Never an active Sentinel while another body exists.
$check($sac([['ASH_097', 0], ['JTL_032', 0]], 'no-sentinelsac') === 'ASH_097', '2 fixture: today Moff Gideon (listed first) goes over bare Krennic');
$check($sac([['ASH_097', 0], ['JTL_032', 0]]) === 'JTL_032', '2: not Moff Gideon (an active Sentinel) — bare Krennic; got ' . $sac([['ASH_097', 0], ['JTL_032', 0]]));
$check($sac([['ASH_097', 0], ['SOR_095', 0]], 'no-sentinelsac') === 'ASH_097', '2 fixture: today Gideon goes over a 3/3 Marine');
$check($sac([['ASH_097', 0], ['SOR_095', 0]]) === 'SOR_095', '2: the Marine, not Gideon; got ' . $sac([['ASH_097', 0], ['SOR_095', 0]]));
$check($sac([['ASH_048', 0], ['JTL_032', 0]]) === 'JTL_032', '2: not the Imperial Armored Commando (Sentinel); got ' . $sac([['ASH_048', 0], ['JTL_032', 0]]));
// Koska is an ACTIVE Sentinel only with a token unit out: with a Spy there, the Spy goes, then … never Koska over the Marine.
$check($sac([['ASH_079', 0], ['SEC_T01', 0], ['SOR_095', 0]]) === 'SEC_T01', '2: Koska + a Spy: the Spy');
$check($sac([['ASH_079', 0], ['SOR_095', 0], ['SEC_T01', 0]]) !== 'ASH_079', '2: Koska (active with the Spy out) is never the one');

// 2b) A DOOMED Sentinel (their ready Marine kills it this round) is not protected — it dies anyway: Gideon on 1 HP left vs a healthy Marine.
$build(function ($b) {
    $b->MyLeader('LAW_008', true); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
    $b->WithGroundUnitForPlayer(1, 'ASH_097', false, 4); $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
$l = SWUBotLegalActions($gameName, 1);
$p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, '');
$check(strval((@GetZoneObject(strval($p['cardID'] ?? '')))->CardID ?? '') === 'ASH_097', '2b: a doomed Gideon (1 HP left) is the sacrifice; got ' . $p['cardID']);

bot_test_finish();
