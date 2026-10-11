<?php
// Feature 'pitchtarget' (p42) + the lookahead's exact restore (bot_lookaheadremoved_test) — OWNER RULING 2026-10-08 (Mando Colossus): "the best Turn 2 play is to play Reckless Sacrifice when you have any
// of the Villainy 1-drops in hand. this deals 5 damage to even the highest hp meta turn 1 plays like Gungi".
// ASH_163 Reckless Sacrifice (2): "Discard a unit from your hand. Deal 5 damage to a unit that costs more than the discarded card." The
// deck's Villainy 1-drop, LAW_097 Imperial Door Technician (Vigilance/Villainy), is off-aspect for Mando (Aggression/Heroism) + Colossus
// (Vigilance) — a 3-cost 2/2 to play, the ideal card to discard. Turn 2 = round 2, 3 resources: Reckless Sacrifice, then claim the
// initiative with the last resource for Mando's draw ('mandoclaim').
// Probed 2026-10-08: the line held only when the event was FIRST in hand — the dud gate's lookahead mis-restored the hand
// (bot_lookaheadremoved_test), and the discard pick was a tie on keep value (C below).
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · ASH_163 Reckless Sacrifice · LAW_097 Imperial Door Technician
//   · SEC_148 Karis Nemik · ASH_031 Hera Syndulla · LOF_093 Gungi (2/5) · LOF_011 (their leader) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_recklessturn2_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-pitchtarget') === ['pitchtarget'], 'pitchtarget is switchable');
$check(in_array('pitchtarget', SWUBotFeatureGroups()['p42'] ?? [], true), 'pitchtarget is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hardcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$handCard = fn(string $mz) => preg_match('/^myHand-(\d+)/', $mz, $m) ? strval(GetHand(1)[intval($m[1])]->CardID ?? '') : '';
// Round 2, 3 resources, their Gungi (2/5) from turn 1. The Door Technician is listed LAST, so no first-listed tiebreak picks it.
$board = function (array $hand) use ($build) {
    $build(function ($b) use ($hand) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 3); $b->WithCurrentRoundBeing(2);
        $b->TheirLeader('LOF_011'); $b->WithGroundUnitForPlayer(2, 'LOF_093', false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};

// A) Karis, Hera, Reckless Sacrifice, Door Technician: Reckless Sacrifice first …
$board(['SEC_148', 'ASH_031', 'ASH_163', 'LAW_097']);
$p = $pick();
$check($handCard($p) === 'ASH_163', 'A: Turn 2 — Reckless Sacrifice; got ' . $p . ' ' . $handCard($p));
$act(1, 10002, $p);
// … discarding the Villainy 1-drop (a 2-cost discard would leave Gungi, cost 2, out of reach) …
$check(strval($botCtx('hardcontrol')['tooltip'] ?? '') === 'Discard_a_unit_from_your_hand', 'A fixture: the discard prompt');
$d = $pick();
$check($handCard($d) === 'LAW_097', 'A: discard the Door Technician; got ' . $d . ' ' . $handCard($d));
$act(1, 100, $d);
for ($k = 0; $k < 3 && $botCtx('hardcontrol')['kind'] === 'decision'; $k++) $act(1, 100, $pick());
// … and Gungi dies to the 5.
$check(count(array_filter(GetGroundArena(2), fn($u) => $u !== null && empty($u->removed))) === 0, 'A: Gungi (5 HP) is defeated');
// Then (after their turn) the claim, with the 1 resource left for Mando's draw.
$act(2, 10001, 'myHealth-0!CustomInput!Pass');
$check($pick() === 'InitiativeCounter-0!CustomInput!TakeInitiative', 'A: then claim and draw with the last resource; got ' . $pick());

// B) No unit cheaper than Gungi to discard (Karis and Hera only): Reckless Sacrifice is not the play.
$board(['SEC_148', 'ASH_031', 'ASH_163']);
$check($handCard($pick()) !== 'ASH_163', 'B: no 1-drop to pitch — not Reckless Sacrifice; got ' . $handCard($pick()));

// C) Feature 'pitchtarget' (p42): the discard is the unit that keeps the 5 in reach. Karis (2) and SHD_160 Reckless Gunslinger (1) are
//    equally worth keeping, so the first listed — Karis — went, and nothing costs more than 2: the 5 fizzled.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 3); $b->WithCurrentRoundBeing(2);
    $b->TheirLeader('LOF_011'); $b->WithGroundUnitForPlayer(2, 'LOF_093', false);
    foreach (['ASH_163', 'SEC_148', 'SHD_160'] as $c) $b->WithCardInHandForPlayer(1, $c);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10002, 'myHand-0!FSM!');
$check($handCard($pick('no-pitchtarget')) === 'SEC_148', 'C fixture: today Karis (the tie\'s first); got ' . $handCard($pick('no-pitchtarget')));
$check($handCard($pick()) === 'SHD_160', 'C: discard the Gunslinger — Gungi stays in reach; got ' . $handCard($pick()));

bot_test_finish();
