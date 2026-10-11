<?php
// Feature 'anakinpitch' (p42) — OWNER RULING 2026-10-08 (Mando Colossus): "Reckless Sacrifice + the villainy card is also a great way to
// instantly activate Anakin. so if the bot can take this line before the 6R turn, it would be a huge swing in their favor especially
// against aggro". LOF_070 Anakin Skywalker: "When Played: If there is a Heroism card in your discard pile, you may give a unit -3/-3 …
// When Played: If there is a Villainy card in your discard pile, you may give a unit -3/-3 …". ASH_163 Reckless Sacrifice (Heroism event)
// goes to the discard itself, and its "Discard a unit from your hand" pitches LAW_097 Imperial Door Technician (Villainy) — both halves on.
// Fixed: a "Discard a unit from your hand" play is also worth each Anakin half it switches on (W['removal'] each — Anakin's -3/-3, priced
// as 'heropitch' / 'villainpitch' price the pitched card), and is not held as a dud while an Anakin waits in hand to cash it.
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · ASH_163 Reckless Sacrifice · LAW_097 Imperial Door
//   Technician · LOF_070 Anakin Skywalker · SEC_148 Karis Nemik · SOR_095 · LOF_011 (their leader) · SHD_160 Reckless Gunslinger (1).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_anakinpitch_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-anakinpitch') === ['anakinpitch'], 'anakinpitch is switchable');
$check(in_array('anakinpitch', SWUBotFeatureGroups()['p42'] ?? [], true), 'anakinpitch is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hardcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$handCard = fn(string $mz) => preg_match('/^myHand-(\d+)/', $mz, $m) ? strval(GetHand(1)[intval($m[1])]->CardID ?? '') : '';
// Round 3, 4 resources, $hand, $discard in my discard pile, $theirs on their ground.
$board = function (array $hand, array $discard = [], array $theirs = []) use ($build) {
    $build(function ($b) use ($hand, $discard, $theirs) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
        $b->TheirLeader('LOF_011');
        foreach ($theirs as $t) $b->WithGroundUnitForPlayer(2, $t, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($discard as $c) $b->WithCardInDiscardForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$score = function (string $id, array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $c = $botCtx('hardcontrol'); $s = null;
    foreach ($c['actions'] as $i => $a) if (strval($a['cardID']) === $id) $s = SWUBotScoreAction($c, $a, $i);
    SWUBotSetDisabledFeatures([]);
    return $s;
};
$W = SWUBotWeights('hardcontrol', 1);
$rs = 'myHand-0!FSM!';

// A) Empty discard, Anakin in hand, a Karis of theirs to hit: Reckless Sacrifice (Heroism) + the Door Technician (Villainy) switch on
//    BOTH halves — worth two removals more.
$board(['ASH_163', 'LAW_097', 'LOF_070', 'SEC_148'], [], ['SEC_148']);
$d = $score($rs) - $score($rs, ['anakinpitch']);
$check(abs($d - 2 * $W['removal']) < 1e-6, 'A: both Anakin halves — two removals; got ' . json_encode([$d, $W['removal']]));

// B) A Heroism card already in the discard: only the Villainy half is new — one removal.
$board(['ASH_163', 'LAW_097', 'LOF_070', 'SEC_148'], ['SEC_148'], ['SEC_148']);
$d = $score($rs) - $score($rs, ['anakinpitch']);
$check(abs($d - $W['removal']) < 1e-6, 'B: Heroism already there — the Villainy half only; got ' . $d);

// C) No Villainy UNIT to pitch (a Reckless Gunslinger, Aggression; SRI is Villainy but an event): the event's own Heroism is the one half — one removal.
$board(['ASH_163', 'SHD_160', 'LOF_070', 'SEC_148', 'LAW_044'], [], ['SEC_148']);   // + Single Reactor Ignition: Villainy, but an EVENT — not a unit it can pitch
$d = $score($rs) - $score($rs, ['anakinpitch']);
$check(abs($d - $W['removal']) < 1e-6, 'C: no Villainy unit — the Heroism half only; got ' . $d);

// D) No Anakin left in hand or deck: nothing to switch on — unchanged.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
    $b->TheirLeader('LOF_011'); $b->WithGroundUnitForPlayer(2, 'SEC_148', false);
    foreach (['ASH_163', 'LAW_097', 'SEC_148'] as $c) $b->WithCardInHandForPlayer(1, $c);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check(abs($score($rs) - $score($rs, ['anakinpitch'])) < 1e-9, 'D: no Anakin to come — unchanged');

// E) Nothing of theirs to hit (the 5 fizzles): today the dud gate holds it; with an Anakin in hand the pitch alone is the play.
$board(['ASH_163', 'LAW_097', 'LOF_070', 'SEC_148']);
$check($handCard($pick('no-anakinpitch')) !== 'ASH_163', 'E fixture: today not Reckless Sacrifice (a dud); got ' . $handCard($pick('no-anakinpitch')));
$check($handCard($pick()) === 'ASH_163', 'E: Reckless Sacrifice to switch Anakin on; got ' . $handCard($pick()));
$act(1, 10002, $pick());
$dd = $pick();
$check($handCard($dd) === 'LAW_097', 'E: the Door Technician is the pitch; got ' . $handCard($dd));


// F) The Anakin is still in the DECK, not in hand, and nothing to hit: the pitch is worth its value, but a fizzled 5 with no Anakin to
//    cash it soon stays a dud — held, as before.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
    $b->TheirLeader('LOF_011');
    foreach (['ASH_163', 'LAW_097', 'SEC_148'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->WithCardInDeckForPlayer(1, 'LOF_070');
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($handCard($pick()) !== 'ASH_163', 'F: Anakin only in the deck — the fizzling pitch is still held; got ' . $handCard($pick()));


// G) Anakin is the ONLY unit in hand: Reckless Sacrifice would have to pitch Anakin himself — no value, and still held.
$board(['ASH_163', 'LOF_070'], [], ['SEC_148']);
$d = $score($rs) - $score($rs, ['anakinpitch']);
$check(abs($d) < 1e-6, 'G: never pitch the Anakin it is for; got ' . $d);
$check($handCard($pick()) !== 'ASH_163', 'G: not played; got ' . $handCard($pick()));

bot_test_finish();
