<?php
// 2026-10-11 Krennic workshop — the owner's answers to memory `krennic-underperformance-2026-10-11` (bot Krennic 25% vs the exact bot
// Hemlock list the owner beat 4/5). Five default-OFF proposals, group 'krennicws':
//   krennicdeploy (+4/+6) — Q1 "saving for a big unit in play that i know would stick so i can do a big power strike. or if i knew i was
//                           already stabilized i deployed to apply pressure"
//   claimspent / claimbomb — Q2 "in a control deck i sometimes claim early if i have removal in hand and they just played a bomb and i
//                           know they won't play anything else or attack with anything else"
//   nogideon / chimaeraweakest — Q3 "yes": never sacrifice Moff Gideon; Chimaera's friendly pick is the weakest
// Fixtures (dictionary-checked): SOR_038 5/4 (7) · SOR_063 Wing Guard 2/4 Sentinel (3) · SOR_050 5/5 space (6) · ASH_116 Ant Droid 1/2
// (1, When Defeated: draw) · ASH_097 Moff Gideon 2/5 Sentinel (3, When Defeated: return an Imperial unit) · SOR_095 Battlefield Marine 3/3
// (2, vanilla) · SOR_164 Wampa 4/5 (4) · JTL_043 No Glory, Only Results (5) · SOR_128 3/1 (1, vanilla) · LOF_059 2/2 (2, When Defeated:
// draw) · SOR_046 3/7 (4, vanilla) · ASH_052 Chimaera.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_krennicws_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

foreach (['krennicdeploy', 'krennicdeploy4', 'krennicdeploy6', 'claimspent', 'claimbomb', 'nogideon', 'chimaeraweakest'] as $p) {
    $check(in_array($p, SWUBotProposalList(), true) && SWUBotVariantDisabled("try-$p") === ["try:$p"], "$p is a switchable proposal");
}
$check(SWUBotVariantDisabled('try-krennicws') === ['try:krennicdeploy', 'try:claimspent', 'try:claimbomb', 'try:nogideon', 'try:chimaeraweakest'],
    'group krennicws switches all five on');

// ── krennicdeploy: _SWUBotDeployStrikeWaits for LAW_008 ────────────────────────────────────────────────────────────────────────────
$waits = function (array $mine, array $theirs, array $variant, string $leader = 'LAW_008') use ($build) {
    $build(function ($b) use ($mine, $theirs, $leader) {
        $b->MyLeader($leader);
        foreach ($mine as $c) (str_contains(strval(CardArena($c)), 'Space') ? $b->WithSpaceUnitForPlayer(1, $c, true) : $b->WithGroundUnitForPlayer(1, $c, true));
        foreach ($theirs as $c) (str_contains(strval(CardArena($c)), 'Space') ? $b->WithSpaceUnitForPlayer(2, $c, true) : $b->WithGroundUnitForPlayer(2, $c, true));
    });
    SWUBotSetDisabledFeatures($variant); $w = _SWUBotDeployStrikeWaits(1); SWUBotSetDisabledFeatures([]); return $w;
};
// D1: a 2-power host can kill their Ant Droid, but nothing big sticks and I do not hold the board.
$check($waits(['SOR_063'], ['SOR_038', 'ASH_116'], []) === false, 'D1 off: the any-kill gate deploys (2 power kills the 1/2 Ant Droid)');
$check($waits(['SOR_063'], ['SOR_038', 'ASH_116'], ['try:krennicdeploy']) === true, 'D1 krennicdeploy: no big host, not stabilised -> wait');
// D2: a 5/4 host faces only a 2-power Wing Guard on the ground (it sticks); their 5/5 ship keeps me from holding the board.
$check($waits(['SOR_038'], ['SOR_063', 'SOR_050'], ['try:krennicdeploy']) === false, 'D2 krennicdeploy: a 5-power host that sticks -> deploy for the big strike');
$check($waits(['SOR_038'], ['SOR_063', 'SOR_050'], ['try:krennicdeploy4']) === false, 'D2 krennicdeploy4: bar 4 -> deploy');
$check($waits(['SOR_038'], ['SOR_063', 'SOR_050'], ['try:krennicdeploy6']) === true, 'D2 krennicdeploy6: bar 6, a 5-power host is not big -> wait');
// D3: the 5/4 host faces another 5/4 — it would not stick.
$check($waits(['SOR_038'], ['SOR_038'], []) === false, 'D3 off: the any-kill gate deploys (5 kills their 5/4)');
$check($waits(['SOR_038'], ['SOR_038'], ['try:krennicdeploy']) === true, 'D3 krennicdeploy: the host dies to their 5/4, not stabilised -> wait');
// D4: stabilised — my Sentinel walls their only attacker and I out-power them.
$check($waits(['SOR_063'], ['ASH_116'], ['try:krennicdeploy']) === false && _SWUBotStabilised(1), 'D4 krennicdeploy: stabilised -> deploy to apply pressure');
// D5: another leader is untouched.
$check($waits(['SOR_038'], ['SOR_038'], ['try:krennicdeploy'], 'SOR_014') === false, 'D5 krennicdeploy: a non-Krennic leader keeps the old gate');
// Stabilised needs BOTH a slow enemy clock and the stronger board: 6 power vs their 5, on a full base (clock 6) vs on 8 HP (clock 2).
$stab = function (int $baseDamage) use ($build) {
    $build(function ($b) use ($baseDamage) {
        $b->MyBase('SOR_024', $baseDamage);
        $b->WithGroundUnitForPlayer(1, 'SOR_128', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithGroundUnitForPlayer(2, 'SOR_038', true);
    });
    return _SWUBotStabilised(1);
};
$check($stab(0) === true, 'stabilised: their clock on a full base is 6 rounds and I out-power them');
$check($stab(22) === false, 'not stabilised: on 8 HP their 5/4 kills me in 2 rounds, however my board looks');

// ── claimspent / claimbomb: their bomb (SOR_038, cost 7) just played; I hold No Glory one resource short ──────────────────────────────
$build(function ($b) { $b->WithCardInHandForPlayer(1, 'JTL_043'); });
$noGlory = intval(SWUComputePlayCost(1, GetHand(1)[0]));
$scene = function (array $o = []) use ($build, $noGlory) {
    $build(function ($b) use ($o, $noGlory) {
        $b->MyLeader('SOR_014', false, false, true);                         // Epic Action used: no deploy on offer
        $b->TheirLeader('SOR_014', false);                                   // their leader exhausted: no Action left
        $b->FillResourcesForPlayer(1, 'SOR_095', $noGlory - 1);
        $b->WithCardInHandForPlayer(1, 'JTL_043');
        $b->WithGroundUnitForPlayer(2, $o['bomb'] ?? 'SOR_038', false);     // just played: exhausted, swings next round
        if (!empty($o['oppRes'])) $b->FillResourcesForPlayer(2, 'SOR_095', $o['oppRes']);
        if (!empty($o['oppReady'])) $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
        if (!empty($o['oppLeader'])) $b->TheirLeader('LAW_008', true);       // a ready leader with an Action ([Exhaust, defeat a friendly unit])
        $b->WithInitiativePlayerBeing($o['init'] ?? 2);
        if (!empty($o['claimed'])) $b->WithInitiativeClaimed();
    });
};
$INIT = 'InitiativeCounter-0!CustomInput!TakeInitiative';
$rule = function (array $variant) use ($botCtx) {
    SWUBotSetDisabledFeatures($variant); $p = (SWUBotRulesAfterFilter()['initiative-for-answer'])($botCtx('control')); SWUBotSetDisabledFeatures([]);
    return $p === null ? null : strval($p['cardID']);
};
$initScore = function (array $variant) use ($botCtx, $INIT) {
    SWUBotSetDisabledFeatures($variant); $ctx = $botCtx('control'); $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $INIT) $s = SWUBotScoreAction($ctx, $a, $i);
    SWUBotSetDisabledFeatures([]); return $s;
};
$scene();
$check(_SWUBotOpponentSpent(1) && strval(GetInitiativeCounter()) === 'P2_UNCLAIMED', 'scene: the opponent is spent and holds the unclaimed initiative');
$check($rule(['try:claimbomb']) === $INIT, 'claimbomb: removal next round for their fresh bomb, opponent spent -> claim early');
$check($rule([]) === null, 'claimbomb: inert when off');
$check($initScore(['try:claimspent']) === $initScore([]), 'claimspent: a spent opponent -> the claim scores as before');
$scene(['init' => 1]);
$check($rule(['try:claimbomb']) === $INIT && $rule(['try:initiative']) === null, 'claimbomb: also while I hold the unclaimed initiative (a spent opponent can still take it); the old rule did not');
$scene(['oppRes' => 1]);
$check(!_SWUBotOpponentSpent(1) && $rule(['try:claimbomb']) === null, 'claimbomb: the opponent still has a ready resource -> no early claim');
$check($initScore(['try:claimspent']) < 0.0 && $initScore([]) > 0.0, 'claimspent: the opponent can still act -> pass (claim below 0) instead of claiming');
$scene(['bomb' => 'SOR_164']);
$check($rule(['try:claimbomb']) === null, 'claimbomb: a 4-cost Wampa is not a bomb -> no early claim');
$scene(['oppReady' => true]);
$check(!_SWUBotOpponentSpent(1) && $rule(['try:claimbomb']) === null, 'not spent: they still have a ready attacker');
$scene(['oppLeader' => true]);
$check(!_SWUBotOpponentSpent(1) && $rule(['try:claimbomb']) === null, 'not spent: their ready leader still has an Action');
$scene(['claimed' => true]);
$check($rule(['try:claimbomb']) === null, 'claimbomb: the initiative is already claimed this round -> nothing to take');
$scene(['oppRes' => 1]);
$aggroScore = function (array $variant) use ($botCtx, $INIT) {
    SWUBotSetDisabledFeatures($variant); $ctx = $botCtx('aggro'); $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $INIT) $s = SWUBotScoreAction($ctx, $a, $i);
    SWUBotSetDisabledFeatures([]); return $s;
};
$check($aggroScore(['try:claimspent']) === $aggroScore([]), 'claimspent: the aggro wing is untouched');

// ── nogideon: Moff Gideon is priced out of every sacrifice ──────────────────────────────────────────────────────────────────────────
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'ASH_097', true, 3);   // Gideon on 2 HP with their 3/3 in the arena: doomed, cheap fodder today
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$cost = function (string $mz, array $variant) { SWUBotSetDisabledFeatures($variant); $c = SWUBotSacrificeCost(SWUBotViewForMz(1, $mz)); SWUBotSetDisabledFeatures([]); return $c; };
$check($cost('myGroundArena-0', []) < $cost('myGroundArena-1', []), 'nogideon off: a doomed Gideon is cheaper fodder than a healthy Marine (the 34 traced sacs)');
$check($cost('myGroundArena-0', ['try:nogideon']) === SWU_BOT_NEVER_SAC_COST, 'nogideon: Gideon is never fodder');
$check($cost('myGroundArena-1', ['try:nogideon']) === $cost('myGroundArena-1', []), 'nogideon: other units are unchanged');

// ── chimaeraweakest: Chimaera's friendly pick (the bot_chimaera_test harness) ──────────────────────────────────────────────────────
$friendlyPick = function (array $mine, array $theirs, string $variant = '') use (&$gameName, $build, $act) {
    $build(function ($b) use ($mine, $theirs) {
        $b->MyLeader('LAW_008', true, false, true); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
        $b->FillResourcesForPlayer(1, 'SOR_095', 10);
        $b->WithCardInHandForPlayer(1, 'ASH_052');
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, true);
        foreach ($theirs as $c) $b->WithGroundUnitForPlayer(2, $c, true);
    });
    $legal = SWUBotLegalActions($gameName, 1); $play = null;
    foreach ((array)$legal['actions'] as $a) if (str_starts_with(strval($a['cardID'] ?? ''), 'myHand-0')) { $play = $a; break; }
    if ($play === null) return 'Chimaera was not playable';
    $act(1, intval($play['mode'] ?? 10002), strval($play['cardID']));
    $legal2 = SWUBotLegalActions($gameName, 1);
    if (stripos(strval($legal2['decisionTooltip'] ?? ''), 'friendly') === false) return 'unexpected prompt: ' . strval($legal2['decisionTooltip'] ?? '');
    $pick = SWUBotHeuristicChoose('midrange', (array)$legal2['actions'], $legal2, $variant);
    $v = SWUBotViewForMz(1, strval($pick['cardID'] ?? ''));
    return $v === null ? strval($pick['cardID'] ?? '') : strval($v['cardID']);
};
$check($friendlyPick(['SOR_128', 'LOF_059'], ['SOR_046']) === 'LOF_059', 'chimaeraweakest off: the 2-drop whose When Defeated draws is the cheaper sacrifice');
$check($friendlyPick(['SOR_128', 'LOF_059'], ['SOR_046'], 'try-chimaeraweakest') === 'SOR_128', 'chimaeraweakest: the weakest unit (the 1-cost 3/1) goes');
$check($friendlyPick(['ASH_097', 'SOR_046'], ['SOR_038'], 'try-krennicws') === 'SOR_046', 'krennicws: Chimaera never takes Gideon, even as the weakest by value');

bot_test_finish();
