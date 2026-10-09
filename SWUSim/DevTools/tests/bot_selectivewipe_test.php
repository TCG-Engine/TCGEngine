<?php
// Feature 'selectivewipe' (p42) — USER REPORT 2026-10-09 (Krennic Blue bot vs Maul Blue, Bot Practice): "didn't do anything on the 7
// resource turn. didn't play a unit, and didn't deploy". Round 6, an empty board of its own, 7 resources: its first action was to take
// the initiative — which ends its round. Probed over 300 random hands from the list: 54 (18%) did exactly that.
// Cause: 'wipeinit' (p36) holds every unit play into the arena of the wipe planned for next round — and it planned ASH_053 Pre Vizsla
// ("When Played: Defeat any number of non-leader units with a total of 6 or less remaining HP"), castable at 8 next round. That wipe is
// SELECTIVE — its player chooses the units — so it never takes my own; holding my units for it is pure loss. ('deploystrike' then held
// the Krennic deploy too: its When Deployed strike needs another friendly unit, and none could be played.)
// Fixed: a wipe that reads "Defeat any number of …" does not hold my unit plays. A wipe that takes everything (SRI "Defeat all units")
// still does.
// Fixtures (dictionary-checked): LAW_008 Director Krennic · ASH_019 · LAW_039 Latts Razzi (3; not a Sentinel, not Krennic fodder — 'mitigation' would release either) · ASH_053 Pre Vizsla (8) ·
//   LAW_044 Single Reactor Ignition (8) · HMW_016 Maul · JTL_020 · LOF_070 Anakin Skywalker · ASH_194 Snub Fighter Squadron · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_selectivewipe_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-selectivewipe') === ['selectivewipe'], 'selectivewipe is switchable');
$check(in_array('selectivewipe', SWUBotFeatureGroups()['p42'] ?? [], true), 'selectivewipe is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$handCard = fn(string $mz) => preg_match('/^myHand-(\d+)/', $mz, $m) ? strval(GetHand(1)[intval($m[1])]->CardID ?? '') : $mz;
// The reported round 6: Krennic undeployed, 7 resources, base on 4, nothing of mine in play; their Anakin + Snub Fighter Squadron ready.
$board = function (string $wipe, int $res = 7) use ($build) {
    $build(function ($b) use ($wipe, $res) {
        $b->MyLeader('LAW_008', true); $b->MyBase('ASH_019', 4); $b->FillResourcesForPlayer(1, 'SOR_095', $res); $b->WithCurrentRoundBeing(6);
        $b->TheirLeader('HMW_016'); $b->TheirBase('JTL_020', 9);
        $b->WithGroundUnitForPlayer(2, 'LOF_070', true); $b->WithSpaceUnitForPlayer(2, 'ASH_194', true, 1);
        $b->WithCardInHandForPlayer(1, 'LAW_039'); $b->WithCardInHandForPlayer(1, $wipe);
        for ($k = 0; $k < 20; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};

// A) Pre Vizsla planned for next round: today the unit is held and the bot takes the initiative; fixed, it is played.
$board('ASH_053');
$check(str_contains($pick('no-selectivewipe'), 'TakeInitiative'), 'A fixture: today it takes the initiative (the reported line); got ' . $pick('no-selectivewipe'));
$check($handCard($pick()) === 'LAW_039', 'A: Latts Razzi is played — Pre Vizsla never takes my units; got ' . $handCard($pick()));

// B) Single Reactor Ignition ("Defeat all units"; 10 for this seat, off-aspect) planned for next round — 9 resources now, 10 next: the
//    unit would die to it — still held, as before.
$board('LAW_044', 9);
$check($pick() === $pick('no-selectivewipe'), 'B: a full wipe still holds the play — unchanged; got ' . json_encode([$pick(), $pick('no-selectivewipe')]));
$check($handCard($pick()) !== 'LAW_039', 'B: Latts is not played into SRI; got ' . $handCard($pick()));

bot_test_finish();
