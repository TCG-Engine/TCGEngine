<?php
// Feature 'thrawnwd' (p42) — JTL_002 Grand Admiral Thrawn, …How Unfortunate: "When you use a 'When Defeated' ability: You may exhaust
// this leader. If you do, use that ability again." (deployed: once each round, no exhaust). Leader audit 2026-10-08: the reuse YESNO was
// answered YES 7,185 of 7,190 — including SEC_215 Emissary's Sheathipede ("Each opponent may ready a resource") 95 times and Stolen
// AT-Hauler ("Choose an opponent … they may play this unit … for free") 21, i.e. Thrawn's exhaust spent to help the opponent again; and
// nothing priced the doubling — a sacrifice or a trade counted a When Defeated payback once. Fixed: a reuse that helps an opponent is
// declined; while the reuse is available, a unit's When Defeated payback counts twice in what losing it costs.
// Fixtures (dictionary-checked): JTL_002 Thrawn · SEC_215 Emissary's Sheathipede (WD: each opponent may ready a resource) · JTL_033 Onyx
//   Squadron Brute (WD: heal 2 damage from a base) · LOF_213 The Legacy Run (5, WD: 6 damage) · SOR_095 · ASH_099.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_thrawnwd_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-thrawnwd') === ['thrawnwd'], 'thrawnwd is switchable');
$check(in_array('thrawnwd', SWUBotFeatureGroups()['p42'] ?? [], true), 'thrawnwd is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Thrawn's front side ($ready), my base on 5 damage, my unit $unit in space; defeat it when $defeat — its When Defeated, then the offer.
$board = function (string $unit, bool $ready = true, bool $defeat = true) use ($build) {
    $build(function ($b) use ($unit, $ready) {
        $b->MyLeader('JTL_002', $ready); $b->MyBase('SOR_020', 5); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(4);
        $b->WithSpaceUnitForPlayer(1, $unit, false); $b->WithSpaceUnitForPlayer(2, 'ASH_099', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    if (!$defeat) return;
    global $playerID; $playerID = 1;
    ob_start(); SWUDefeatUnit(1, 'mySpaceArena-0'); (new DecisionQueueController())->ExecuteStaticMethods(1, '-'); ob_end_clean();
};
// Answer the When Defeated's own prompts (the Brute's "which base") with the bot, up to Thrawn's offer.
$toOffer = function () use ($botCtx, $act, &$gameName) {
    for ($k = 0; $k < 4 && $botCtx('midrange')['kind'] === 'decision' && !str_ends_with($botCtx('midrange')['tooltip'], '_again_(Thrawn)?'); $k++) {
        $l = SWUBotLegalActions($gameName, 1); $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, '');
        $act(1, 100, strval($p['cardID'] ?? 'PASS'));
    }
};
$isOffer = fn(string $t) => str_ends_with($t, '_again_(Thrawn)?');

// A) The Sheathipede's "Each opponent may ready a resource": today reused (YES); fixed, declined.
$board('SEC_215');
$check($isOffer($botCtx('midrange')['tooltip']), 'A fixture: the Thrawn offer is pending; got ' . $botCtx('midrange')['tooltip']);
$check($pick('no-thrawnwd') === 'YES', 'A fixture: today the opponent-helping ability is reused');
$check($pick() === 'NO', 'A: it helps the opponent — declined; got ' . $pick());
// B) Onyx Squadron Brute's "Heal 2 damage from a base" (mine is damaged): reused.
$board('JTL_033'); $toOffer();
$check($isOffer($botCtx('midrange')['tooltip']), 'B fixture: the Thrawn offer is pending');
$check($pick() === 'YES', 'B: a heal for me — reused; got ' . $pick());

// C) While the reuse is available, a When Defeated pays back twice when its unit is the one lost (a sacrifice, a trade): The Legacy Run
// (5; "When Defeated: Deal 6 damage divided as you choose among enemy units").
$cost = function (bool $thrawnReady, bool $on) use ($board) {
    $board('LOF_213', $thrawnReady, false);
    $v = null; foreach (SWUBotUnits(1) as $u) if ($u['cardID'] === 'LOF_213') $v = $u;
    if (!$on) SWUBotSetDisabledFeatures(['thrawnwd']);
    $c = SWUBotSacrificeCost($v); SWUBotSetDisabledFeatures([]);
    return $c;
};
$check($cost(true, true) < $cost(true, false), 'C: Thrawn ready — The Legacy Run is cheaper to lose (its 6 damage twice); got ' . json_encode([$cost(true, true), $cost(true, false)]));
$check(abs($cost(false, true) - $cost(false, false)) < 1e-9, 'C: Thrawn exhausted — unchanged');

bot_test_finish();
