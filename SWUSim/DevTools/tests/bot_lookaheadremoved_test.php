<?php
// SWUBotLookahead must restore the board EXACTLY — including the entries a play left in their zone flagged 'removed'. Production
// keeps them across a request (a played event stays at its hand index until its play finishes), so a prompt raised mid-play lists
// answers by the UNCOMPACTED index. The lookahead's restore rebuilt the board from the undo payload, which skips removed entries:
// after the first branch every sibling ran on a COMPACTED board, where those indices point at other cards or past the end.
// Found 2026-10-08 (owner ruling: "the best Turn 2 play is Reckless Sacrifice when you have any of the Villainy 1-drops in hand"):
// ASH_163 Reckless Sacrifice at myHand-2 listed its discard "myHand-0&myHand-1&myHand-3"; the Door Technician branch (myHand-3),
// tried after the Karis one, discarded nothing and Gungi survived — the dud gate then held the play whenever it was not first in hand.
// Fixtures (dictionary-checked): ASH_014 · JTL_021 · ASH_163 Reckless Sacrifice · LAW_097 Imperial Door Technician · SEC_148 Karis
//   Nemik · ASH_031 Hera Syndulla · LOF_093 Gungi (2/5) · LOF_011 · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lookaheadremoved_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 3); $b->WithCurrentRoundBeing(2);
    $b->TheirLeader('LOF_011'); $b->WithGroundUnitForPlayer(2, 'LOF_093', false);
    foreach (['SEC_148', 'ASH_031', 'ASH_163', 'LAW_097'] as $c) $b->WithCardInHandForPlayer(1, $c);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10002, 'myHand-2!FSM!');   // Reckless Sacrifice, for real: its discard prompt is pending
$hand = fn() => implode(',', array_map(fn($o) => $o === null ? 'null' : $o->CardID . (empty($o->removed) ? '' : '(X)'), GetHand(1)));
$check($hand() === 'SEC_148,ASH_031,ASH_163(X),LAW_097', 'fixture: the played event stays at hand index 2, removed; got ' . $hand());
$l = SWUBotLegalActions($gameName, 1);
$byId = [];
foreach ($l['actions'] as $a) $byId[strval($a['cardID'])] = $a;
$check(array_keys($byId) === ['myHand-0', 'myHand-1', 'myHand-3'], 'fixture: the discard answers by uncompacted index; got ' . json_encode(array_keys($byId)));
$gungiAfter = fn(array $a) => SWUBotLookahead(1, $a, fn() => ['alive' => count(array_filter(GetGroundArena(2), fn($u) => $u !== null && empty($u->removed)))]);

// A) The restore puts the removed entry back where it was.
$gungiAfter($byId['myHand-0']);
$check($hand() === 'SEC_148,ASH_031,ASH_163(X),LAW_097', 'A: after a lookahead the hand is exactly as before; got ' . $hand());

// B) So a sibling answer tried AFTER another means the same card: discarding the Door Technician (cost 1) lets the 5 hit Gungi (cost 2).
$check(($gungiAfter($byId['myHand-3'])['alive'] ?? -1) === 0, 'B: the Door Technician branch, after the Karis branch, kills Gungi');
$check(($gungiAfter($byId['myHand-0'])['alive'] ?? -1) === 1, 'B: the Karis branch (cost 2 — Gungi is not costlier) leaves Gungi');

bot_test_finish();
