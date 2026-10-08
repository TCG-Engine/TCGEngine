<?php
// Feature 'twoping' (p42) — LOF_009 Darth Maul, Sith Revealed: "Action [Exhaust, use the Force]: Deal 1 damage to a unit and 1 damage to
// a different unit." Both pings are mandatory. Leader audit 2026-10-08: the second ping hit the bot's OWN unit 42% of the time (1,807 of
// them forced — only one enemy unit), because the Action was priced flat and never saw the second prompt; and the 'force' gate read a
// kill only off "-N/-N", so a ping that kills a 1-HP unit counted as no kill and Maul kept the Force (used 9.2% with another Force card
// in hand). Fixed: the Action is the best pair of targets, the second on a DIFFERENT unit (my own when only one enemy is there); a ping
// that defeats a unit is a kill.
// Fixtures (dictionary-checked): LOF_009 · LOF_020 base · SOR_095 Battlefield Marine (3/3) · SOR_225 TIE/ln Fighter (2/1)
//   · LOF_035 (a Force card: the Assassin) — dictionary-checked in bot_force_test.php.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_twoping_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-twoping') === ['twoping'], 'twoping is switchable');
$check(in_array('twoping', SWUBotFeatureGroups()['p42'] ?? [], true), 'twoping is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
// Maul with the Force, no resources; my exhausted Marine; their units; $hand.
$board = function (array $theirGround, array $theirSpace, array $hand = []) use ($build) {
    $build(function ($b) use ($theirGround, $theirSpace, $hand) {
        $b->MyLeader('LOF_009', true); $b->MyBase('LOF_020'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
        foreach ($theirGround as $c) $b->WithGroundUnitForPlayer(2, $c, false);
        foreach ($theirSpace as $c) $b->WithSpaceUnitForPlayer(2, $c, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};

// A) One enemy unit, a 3-HP Marine: 1 chip on it, and the second ping must hit my own Marine. Today the Action is used; fixed, not.
$board(['SOR_095'], []);
$check($pick('no-twoping') === $ability, 'A fixture: today Maul pings with only one enemy unit');
$check($pick() !== $ability, 'A: a chip on theirs + a forced ping on mine — not used; got ' . $pick());
// B) One enemy unit, a 1-HP TIE: the kill is worth the ping on my own Marine — used.
$board([], ['SOR_225']);
$check($pick() === $ability, 'B: kills the TIE (the second ping on my Marine) — used; got ' . $pick());
// C) A ping that KILLS is a kill for the Force gate: two enemies (a 1-HP TIE and a Marine), another Force card in hand. Today the Force
// is kept (no "-N/-N" kill read); fixed, Maul takes the kill.
$board(['SOR_095'], ['SOR_225'], ['LOF_035']);
$check($pick('no-twoping') !== $ability, 'C fixture: today the Force is kept for the other card');
$check($pick() === $ability, 'C: the ping kills the TIE — Maul uses it; got ' . $pick());
// D) Two 3-HP enemy units, another Force card in hand: no kill, so the Force waits for that card — unchanged.
$board(['SOR_095', 'SOR_095'], [], ['LOF_035']);
$check($pick() !== $ability, 'D: no kill and another Force card — the Force waits; got ' . $pick());

bot_test_finish();
