<?php
// Feature 'tuck' (p42) — LOF_016 Qui-Gon Jinn: "Return a friendly non-leader unit to its owner's hand. Play a non-Villainy unit that
// costs less than the returned unit from your hand for free." (front Action [Exhaust, use the Force]; deployed: When this unit completes
// an attack, "You may …" the same). Leader audit 2026-10-08, 48 probe games: the Action was used 120 times and 73 (61%) returned a 1-2 drop
// and played NOTHING (24 x the 1-cost Luke — no unit costs less than 1); the owner's loops (Yoda -> Kelleran, Kelleran -> Depa, Depa ->
// Amidala) happened 4 times; the deployed "you may" was declined 106 of 107. Causes: the Action was a flat W['ability'] once the lookahead
// stopped at its first prompt, and "Return_a_friendly…_to_hand" read as HOSTILE, so the pick took the CHEAPEST unit and the MAY was passed.
// Fixed: the Action, the return pick and the deployed MAY are all priced by the best (returned unit -> cheaper free play) pair.
// Fixtures (dictionary-checked): LOF_016 Qui-Gon · LOF_023 Jedi Temple · HMW_208 Luke (1) · LAW_067 Jyn Erso (2) · LOF_199 Depa Billaba (6)
//   · SEC_101 Queen Amidala (5, When Played: 2 Spy tokens) · SOR_095 (their unit).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_tuck_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-tuck') === ['tuck'], 'tuck is switchable');
$check(in_array('tuck', SWUBotFeatureGroups()['p42'] ?? [], true), 'tuck is in group p42');

$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$mzOf = function (string $cid): string {
    foreach (GetZone('myGroundArena') as $i => $o) if ($o !== null && empty($o->removed) && strval($o->CardID) === $cid) return "myGroundArena-$i";
    return '';
};
// Qui-Gon's front side ready with the Force, no resources (nothing else to play), my $mine exhausted, $hand in hand, their Marine.
$front = function (array $mine, array $hand) use ($build) {
    $build(function ($b) use ($mine, $hand) {
        $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 10; $k++) { $b->WithCardInDeckForPlayer(1, 'HMW_208'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';

// A) Only a 1-cost Luke to return: nothing in hand costs less than 1, so the Action only bounces him. Today it is used; fixed, it is not.
$front(['HMW_208'], ['LAW_067']);
$check($pick('no-tuck') === $ability, 'A fixture: today the Action bounces a 1-drop for nothing');
$check($pick() !== $ability, 'A: no cheaper unit to play — the Action is not used');

// A2) "Costs LESS than": a Youngling Padawan (2) cannot buy Jyn Erso (2). Not used.
$front(['LOF_193'], ['LAW_067']);
$check($pick() !== $ability, 'A2: an equal-cost unit is not cheaper — the Action is not used');

// A3) "Non-Villainy": a Depa (6) on 1 HP left would be worth returning, but the only cheaper unit is an Onyx Squadron Brute (2,
// Villainy). Not used.
$hurtDepa = function (string $inHand) use ($build) {
    $build(function ($b) use ($inHand) {
        $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
        $b->WithGroundUnitForPlayer(1, 'LOF_199', false, 4); $b->WithCardInHandForPlayer(1, $inHand);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 10; $k++) { $b->WithCardInDeckForPlayer(1, 'HMW_208'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$hurtDepa('SOR_095');   // Battlefield Marine (2, Heroism)
$check($pick() === $ability, 'A3 control: the hurt Depa -> a 2-cost Heroism unit IS worth it');
$hurtDepa('JTL_033');
$check($pick() !== $ability, 'A3: the only cheaper unit is Villainy — the Action is not used');

// B) Depa Billaba (6, exhausted) back to hand, Queen Amidala (5) for free: the Action is used.
$front(['LOF_199'], ['SEC_101']);
$check($pick() === $ability, 'B: Depa -> Amidala for free — the Action is used');

// C) The return pick: Luke (1) and Depa (6) both exhausted, Amidala (5) in hand. Today the cheapest (Luke) goes; fixed, Depa.
$front(['HMW_208', 'LOF_199'], ['SEC_101']);
$act(1, 10001, $ability);
$check(str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'), 'C fixture: the return prompt is pending; got ' . $botCtx('midrange')['tooltip']);
$check($pick('no-tuck') === $mzOf('HMW_208'), 'C fixture: today the cheapest unit (Luke) is returned');
$check($pick() === $mzOf('LOF_199'), 'C: Depa is returned — Amidala can then be played free');

// C2) A unit that can still attack this round costs that attack: Queen Amidala (5) READY vs Depa (6) exhausted, Jod Na Nawood (3) in
// hand. Amidala alone would be the better return; with her attack still to come, Depa goes.
$build(function ($b) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
    $b->WithGroundUnitForPlayer(1, 'SEC_101', true); $b->WithGroundUnitForPlayer(1, 'LOF_199', false); $b->WithCardInHandForPlayer(1, 'ASH_219');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 10; $k++) { $b->WithCardInDeckForPlayer(1, 'HMW_208'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10001, $ability);
$check(str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'), 'C2 fixture: the return prompt is pending');
$check($pick() === $mzOf('LOF_199'), 'C2: the exhausted Depa is returned, not the ready Amidala');

// C3) The owner's loop: Yoda (8) has healed and hit, Kelleran Beq (7) is in hand. The Action is used and Yoda goes back (to be cast
// again for another heal), not the Jyn Erso beside him. Yoda's own "When you use the Force" may ask first; it is answered NO here.
$front(['LOF_101', 'LAW_067'], ['LOF_100', 'HMW_208']);
$check($pick() === $ability, 'C3: Yoda -> Kelleran — the Action is used');
$act(1, 10001, $ability);
for ($k = 0; $k < 3 && !str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'); $k++) $act(1, 100, 'NO');
$check(str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'), 'C3 fixture: the return prompt is pending; got ' . $botCtx('midrange')['tooltip']);
$check($pick() === $mzOf('LOF_101'), 'C3: Yoda is returned');

// D) Deployed Qui-Gon completes an attack on their base: "You may return …". Depa exhausted, Amidala in hand: take it (today: PASS).
// No enemy unit, so the attack goes straight to their base (no target prompt) and the "you may return" is next.
$deployed = function (array $mine, array $hand) use ($build, $raiseAttack, $mzOf) {
    $build(function ($b) use ($mine, $hand) {
        $b->MyLeader('LOF_016', true, true, true, 'unit'); $b->MyBase('LOF_023'); $b->WithCurrentRoundBeing(7);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 10; $k++) { $b->WithCardInDeckForPlayer(1, 'HMW_208'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $raiseAttack(1, $mzOf('LOF_016'));
};
$deployed(['LOF_199'], ['SEC_101']);
$check(str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'), 'D fixture: the deployed "you may return" is pending; got ' . $botCtx('midrange')['tooltip']);
$check($pick('no-tuck') === 'PASS', 'D fixture: today the deployed return is declined');
$check($pick() === $mzOf('LOF_199'), 'D: Depa is returned after the attack');
// E) …but with only Luke (1) to return, it is still declined.
$deployed(['HMW_208'], ['LAW_067']);
$check($pick() === 'PASS', 'E: deployed, nothing cheaper to play — declined');

bot_test_finish();
