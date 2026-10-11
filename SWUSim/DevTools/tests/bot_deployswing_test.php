<?php
// Feature 'deployswing' (p42) — in a race (either side's clock 3 rounds or less), a deploy that puts a READY leader unit on the board (a
// leader unit enters ready, CR 4.329) is worth that unit's best attack this turn. HMW_007 Darth Vader, Might of the Empire (deployed 5/5, Raid 1; "Other friendly units that cost 3 or more
// gain Raid 1"): the deploy is free, but it scored the flat W['deploy'] (1.5), below most plays and attacks — leader audit 2026-10-08,
// 48 probe games: 47 reached 6 resources, 23 deployed; in the other 24 the game ended that round with the deploy offered 1-5 times and
// never taken (16 losses). Pilot deploys are untouched (no leader unit appears in the lookahead).
// Fixtures (dictionary-checked): HMW_007 Darth Vader (Command/Villainy, 5/5) · HMW_032 base · SOR_095 Battlefield Marine · SOR_225 (theirs)
//   · JTL_009 Boba Fett + ASH_099 Gozanti Assault Carrier (the pilot case).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_deployswing_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-deployswing') === ['deployswing'], 'deployswing is switchable');
$check(in_array('deployswing', SWUBotFeatureGroups()['p42'] ?? [], true), 'deployswing is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softaggro', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$deploy = 'myLeader-0!CustomInput!DeployLeader:Unit';
// Vader undeployed, 6 resources, nothing in hand, my Marine ($ready); their base on $their damage, no ground unit in Vader's way.
$board = function (int $their, bool $ready = true) use ($build) {
    $build(function ($b) use ($their, $ready) {
        $b->MyLeader('HMW_007', true); $b->MyBase('HMW_032'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
        $b->TheirBase('SOR_020', $their); $b->WithGroundUnitForPlayer(1, 'SOR_095', $ready);
        $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
// A) A race (their base on 22 of 30: my Marine's clock is 3): Vader deploys BEFORE the Marine's attack — he swings for 6 this turn too.
// Today the Marine attacks and the deploy waits for the end of the round, where it scores just above a pass.
$board(22);
$check($pick('no-deployswing') === 'myGroundArena-0!FSM!', 'A fixture: today the Marine attacks first; got ' . $pick('no-deployswing'));
$check($pick() === $deploy, 'A: Vader deploys first (a ready 5/5 Raid swing this turn); got ' . $pick());
// B) The deploy is priced above the bare 1.5 by the swing it makes.
$score = function (array $off) use ($botCtx, $deploy) { SWUBotSetDisabledFeatures($off); $c = $botCtx('softaggro'); $s = null; foreach ($c['actions'] as $i => $a) if ($a['cardID'] === $deploy) $s = SWUBotScoreAction($c, $a, $i); SWUBotSetDisabledFeatures([]); return $s; };
$check($score([]) > $score(['deployswing']) + 1.0, 'B: the deploy carries its swing; got ' . json_encode([$score([]), $score(['deployswing'])]));

// B2) No race (their base on 10, my Marine's clock 7; theirs on me none): the deploy is priced as before.
$board(10, false);
$check(abs($score([]) - $score(['deployswing'])) < 1e-9, 'B2: no race — unchanged; got ' . json_encode([$score([]), $score(['deployswing'])]));
// C) A pilot deploy (JTL_009 Boba, a ready Gozanti to fly): no leader UNIT appears, so nothing is added — the ship's own attack is not his.
$build(function ($b) {
    $b->MyLeader('JTL_009', true); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
    $b->WithSpaceUnitForPlayer(1, 'ASH_099', true); $b->WithSpaceUnitForPlayer(1, 'ASH_099', true);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check(abs($score([]) - $score(['deployswing'])) < 1e-9, 'C: a pilot deploy — unchanged; got ' . json_encode([$score([]), $score(['deployswing'])]));

bot_test_finish();
