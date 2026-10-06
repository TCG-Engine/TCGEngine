<?php
// Feature 'condemnfore' (p36) — an attacker carrying an ENEMY Condemn (SEC_038: "On Attack: the defending player may disclose
// Vigilance Villainy. If they do, this unit gets -6/-0") swings for 6 less whenever the defender can disclose. Reprint_Cad (Hemlock
// Red) vs Ninin (Ahsoka Yellow), 2026-10-04: Condemned Ahsoka attacked three times and was blanked every time. Owner: build it.
// Expected attack power = max(0, attackPower - 6) when the defender can disclose. Who knows that: the DEFENDER itself (viewer
// seat = the Condemn's controller) reads its own hand exactly; anyone else only sees the defender's hand SIZE — a player who
// cast Condemn plays Vigilance/Villainy, so a non-empty hand is assumed to disclose.
// Fixtures (dictionary-checked): SOR_095 Battlefield Marine 3/3 · SEC_038 Condemn · HMW_071 Ravage (Vigilance/Villainy) ·
//   LAW_172 Storm Raider (Aggression/Villainy) · ASH_009 Ahsoka Tano · HMW_003 · HMW_027 · ASH_026 Freetown (30)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_condemnfore_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-condemnfore') === ['condemnfore'], 'condemnfore is switchable');
$check(in_array('condemnfore', SWUBotFeatureGroups()['p36'] ?? [], true), 'condemnfore is in group p36');
// Seat 1 (Ahsoka's side): a ready Battlefield Marine carrying seat 2's Condemn. Seat 2 (Hemlock's side): $hand, base on $hp.
$board = function (array $hand, int $theirDamage = 27) use ($build) {
    $build(function ($b) use ($hand, $theirDamage) {
        $b->MyLeader('ASH_009', false); $b->TheirLeader('HMW_003', false); $b->TheirBase('HMW_027', $theirDamage);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SEC_038', 2)]);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(2, $c);
    });
};
$ap = function (?int $viewer, bool $on = true) {
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['condemnfore'];
    if ($viewer === null) unset($GLOBALS['SWUBotViewerSeat']); else $GLOBALS['SWUBotViewerSeat'] = $viewer;
    $x = SWUBotUnits(1)[0]['attackPower'];
    $GLOBALS['SWUBotDisabledFeatures'] = []; unset($GLOBALS['SWUBotViewerSeat']);
    return $x;
};

// A) The attacker's view: the defender holds a card — expect the -6 (3 -> 0). Off: 3.
$board(['LAW_172']);
$check($ap(1, false) === 3, 'A fixture: today the Condemned Marine attacks for 3');
$check($ap(1) === 0, 'A: seat 1 expects the disclose (defender has a card) — 0; got ' . $ap(1));
// B) The defender's own view is EXACT: Storm Raider cannot disclose Vigilance — full 3. With Ravage it can — 0.
$check($ap(2) === 3, 'B: seat 2 knows its hand cannot disclose — 3; got ' . $ap(2));
$board(['HMW_071']);
$check($ap(2) === 0, 'B: seat 2 holds Ravage and can disclose — 0; got ' . $ap(2));
// C) An empty defending hand: no disclose possible — full power for everyone.
$board([]);
$check($ap(1) === 3 && $ap(2) === 3, 'C: empty hand — full 3');
// D) The decision: their base on 3 HP, the Condemned Marine my only attacker. Today rule 2 calls it lethal; fixed, it is not.
$board(['LAW_172'], 27);
$GLOBALS['SWUBotDisabledFeatures'] = ['condemnfore']; $off = SWUBotLethalNow(1, 2);
$GLOBALS['SWUBotDisabledFeatures'] = []; $GLOBALS['SWUBotViewerSeat'] = 1; $on = SWUBotLethalNow(1, 2); unset($GLOBALS['SWUBotViewerSeat']);
$check($off === true && $on === false, 'D: a "lethal" made of a Condemned attacker is no lethal; got ' . var_export([$off, $on], true));

bot_test_finish();
