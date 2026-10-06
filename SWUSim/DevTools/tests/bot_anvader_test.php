<?php
// Feature 'anvader' (Part 29, 2026-10-03) — a hand-size attack event is worth the attack it makes. Owner, on the Vader
// list (fixture arenabot-ideas/darth-vader_law_blue-force): "hold cards until you can use Aggressive Negotiations for a
// double buffed attack". SEC_179 Aggressive Negotiations: "Attack with a unit. For this attack, it gets +1/+0 for each
// card in your hand." With deployed LAW_011 Darth Vader ("On Attack: Discard any number of cards from your hand. Deal
// damage … equal to the number discarded") the hand pays twice: once as power, again as discarded damage. Measured
// before: 0.04 AN casts a game, none with Vader — its play value was develop × cost, the attack it makes unpriced.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_anvader_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-anvader') === ['anvader'], 'anvader is switchable; got ' . json_encode(SWUBotVariantDisabled('no-anvader')));
$check(in_array('anvader', SWUBotFeatureGroups()['p29'] ?? [], true), 'anvader is in group p29');

$legal = fn() => SWUBotLegalActions($GLOBALS['gameName'], 1);
$pick = fn(array $l, string $v) => SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $v);
// Deployed Vader (ready unless $ready false) on Nightsister Lair, $res resources, $hand, plus $more($b).
$board = function (array $hand, int $res = 3, bool $ready = true, ?callable $more = null) {
    return function ($b) use ($hand, $res, $ready, $more) {
        $b->MyLeader('LAW_011', $ready, true, false, 'unit'); $b->MyBase('LOF_020');
        $b->FillResourcesForPlayer(1, 'LOF_059', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        if ($more !== null) $more($b);
    };
};
// The first free-play choice, and — when it plays AN — the unit it then attacks with.
$open = function (callable $b, string $variant) use ($build, $act, $legal, $pick) {
    $build($b);
    $l = $legal(); $p = $pick($l, $variant); $id = strval($p['cardID'] ?? '');
    $an = null;
    foreach (GetHand(1) as $i => $o) if ($o->CardID === 'SEC_179') $an = "myHand-$i!FSM!";
    if ($id !== $an) return [$id, null];
    $act(1, intval($p['mode'] ?? 10001), $id);
    $l = $legal();
    if (($l['kind'] ?? '') !== 'decision') return [$id, 'NO-ATTACKER-PROMPT'];
    $who = strval($pick($l, $variant)['cardID'] ?? '');
    $v = SWUBotViewForMz(1, $who);
    return [$id, $v['cardID'] ?? $who];
};

// A) THE DOUBLE-BUFFED SWING: Vader ready, AN + three cards. AN with Vader is a 9-power attack plus up to 3 discarded damage;
// a plain Vader attack is 6. Today the bot just attacks.
$boardA = $board(['SEC_179', 'LOF_059', 'LOF_061', 'JTL_033']);
[$offFirst] = $open($boardA, 'no-anvader');
$check($offFirst === 'myGroundArena-0!FSM!', 'A fixture: today the bot attacks with Vader directly; got ' . $offFirst);
[$first, $who] = $open($boardA, '');
$check($first === 'myHand-0!FSM!', 'A: the bot casts Aggressive Negotiations first; got ' . $first);
$check($who === 'LAW_011', 'A: …and attacks with Vader; got ' . json_encode($who));
// B) The attacker is the one the hand pays most: a ready Ninth Sister (8/7) sits beside Vader (6/8). On power alone she is
// the bigger swing (11 vs 9), but Vader's On Attack cashes the hand a second time (9 + 3 discarded) — AN goes to Vader.
$boardB = $board(['SEC_179', 'LOF_059', 'LOF_061', 'JTL_033'], 3, true, fn($b) => $b->WithGroundUnitForPlayer(1, 'ASH_148'));
[, $whoB] = $open($boardB, '');
$check($whoB === 'LAW_011', 'B: AN goes to Vader over another ready unit; got ' . json_encode($whoB));
// C) NOTHING TO CASH: AN is the only card in hand — +0/+0, so a plain attack does the same and keeps AN.
[$firstC] = $open($board(['SEC_179']), '');
$check($firstC !== 'myHand-0!FSM!', 'C: with an empty hand behind it AN is not cast; got ' . $firstC);
// D) Vader EXHAUSTED and no other unit: AN has no attacker — not cast.
[$firstD] = $open($board(['SEC_179', 'LOF_059', 'LOF_061'], 3, false), '');
$check($firstD !== 'myHand-0!FSM!', 'D: no ready attacker — AN is not cast; got ' . $firstD);

// E) The hand IS the power: for a unit with no On Attack of its own (Ninth Sister, Vader exhausted) attacking a base, three
// cards behind the event add exactly three points of base damage at the base rate.
$build($board(['SEC_179', 'LOF_059', 'LOF_061', 'JTL_033'], 3, false, fn($b) => $b->WithGroundUnitForPlayer(1, 'ASH_148')));
$ns = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'ASH_148') $ns = $v;
$W = SWUBotWeights('midrange', 1); $ctx = $botCtx('midrange');
$gain = _SWUBotHandSwingValue($ctx, $ns, 3, $W) - _SWUBotHandSwingValue($ctx, $ns, 0, $W);
$check(abs($gain - 3 * $W['base']) < 1e-9, 'E: three cards add 3 x W[base] to a base swing; got ' . round($gain, 4));

bot_test_finish();
