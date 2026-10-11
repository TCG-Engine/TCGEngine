<?php
// Feature 'keepbody' (p40) — in rounds 1-3 against an aggro leader, Krennic's Credit Action ("Action [Exhaust, defeat a friendly unit]:
// Create a Credit token") does not sacrifice my ONLY unit unless its When Defeated draws a card. Autopsy 2026-10-06: the bot ended R1
// with 0.2 units (a 1-drop, sacrificed at once), the owner with 1.2 (the Krennic unit stays; only the Ant Droid beside it goes). Owner
// ruling, same day: "Sac it if it draws" — the only unit may go when it replaces itself (Ant Droid / Nightsister); a body without a draw
// (Imperial Door Technician, Onyx Squadron Brute heal) stays.
// Fixtures (dictionary-checked): LAW_008 Director Krennic · ASH_019 · LAW_097 Imperial Door Technician (WD: heal 2) · ASH_116 Ant Droid
//   (WD: draw) · JTL_033 Onyx Squadron Brute (WD: heal 2) · ASH_009 + SOR_030 (Ahsoka, aggro) · HMW_003 (Hemlock, not aggro) · LAW_038
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_keepbody_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-keepbody') === ['keepbody'], 'keepbody is switchable');
$check(in_array('keepbody', SWUBotFeatureGroups()['p40'] ?? [], true), 'keepbody is in group p40');
// Round $rnd, Krennic ready, resources spent (nothing to play), my exhausted $mine; their $leader with a Lepi Lookout. Does the bot use
// the Credit Action now?
$uses = function (array $mine, string $variant, int $rnd = 1, string $leader = 'ASH_009') use ($build, &$gameName) {
    $build(function ($b) use ($mine, $rnd, $leader) {
        $b->MyLeader('LAW_008', true); $b->MyBase('ASH_019'); $b->TheirLeader($leader, false); $b->TheirBase('SOR_030');
        $b->WithCurrentRoundBeing($rnd);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        $b->WithGroundUnitForPlayer(2, 'LAW_038', false);
        for ($k = 0; $k < 20; $k++) { $b->WithCardInDeckForPlayer(1, 'ASH_116'); $b->WithCardInDeckForPlayer(2, 'LAW_038'); }
    });
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return str_contains(strval($p['cardID'] ?? ''), 'LeaderAbility');
};
// A) R1 vs Ahsoka, my only unit is a Door Technician (heals, no draw): today it is sacrificed; fixed, it stays.
$check($uses(['LAW_097'], 'no-keepbody') === true, 'A fixture: today the only unit (Door Technician) is sacrificed');
$check($uses(['LAW_097'], '') === false, 'A: the Door Technician — my only body, no draw — stays');
// B) The only unit is an Ant Droid (draws): it may still go.
$check($uses(['ASH_116'], '') === $uses(['ASH_116'], 'no-keepbody'), 'B: an Ant Droid (draws) — unchanged');
// C) Two units: the Action is free to take one — unchanged.
$check($uses(['LAW_097', 'JTL_033'], '') === $uses(['LAW_097', 'JTL_033'], 'no-keepbody'), 'C: two bodies — unchanged');
// D) Round 4: unchanged. E) Not an aggro leader (Hemlock): unchanged.
$check($uses(['LAW_097'], '', 4) === $uses(['LAW_097'], 'no-keepbody', 4), 'D: round 4 — unchanged');
$check($uses(['LAW_097'], '', 1, 'HMW_003') === $uses(['LAW_097'], 'no-keepbody', 1, 'HMW_003'), 'E: vs a non-aggro leader — unchanged');

bot_test_finish();
