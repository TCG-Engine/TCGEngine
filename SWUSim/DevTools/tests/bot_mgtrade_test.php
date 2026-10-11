<?php
// PROPOSAL 'mgtrade' (default OFF) — owner rulings 2026-09-23 (A1/A5b): a MIDRANGE seat that is BEHIND ON BOARD
// trades instead of racing; once ahead it races. Judge the board by POWER, not by body count (A5b).
//
// THE DEFECT IT FIXES. SWUBotTargetValue prices a kill as W['kill'] x the target's VALUE (a cost proxy), while an
// attack on the base is W['base'] x power. A cheap high-power body is therefore under-priced: killing a
// Battlefield Marine (2 cost, 3/3) and surviving is 0.9 x 2 = 1.80 to a midrange seat, against 2.40 for swinging
// at the base for 4 — so the bot hits the base. Traced 2026-09-23: 73% of a midrange bot's attacks went at the
// base across 40 games while it lost the board 3.1 units to 4.8 and died in round 6.
// THE FIX: while behind, a kill ALSO earns the damage it PREVENTS — the target's power x W['threat'], which
// SWUBotWeights sets to W['base'] for a midrange seat and leaves 0 for every other style. A kill and a swing are
// then priced in the same currency. Ahead on board, the term is zero and the seat races exactly as it does today.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_mgtrade_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-mgtrade') === ['try:mgtrade'], 'proposal mgtrade is registered');
$ON = ['try:mgtrade'];
// My Koska Reeves 4/4 (ready) against $theirs. Two enemy units put me behind on power; one small one leaves me ahead.
$board = function (array $theirs) use ($build) {
    $build(function ($b) use ($theirs) {
        $b->MyLeader('ASH_005', true, false, false); $b->MyBase('JTL_024'); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $b->WithGroundUnitForPlayer(1, 'ASH_079', true);              // Koska Reeves 4/4, ready
        $b->TheirLeader('ASH_009', true);
        foreach ($theirs as $t) $b->WithGroundUnitForPlayer(2, $t, true);
    });
};
// Score the attack-target decision the bot is actually asked (MZCHOOSE over their units and their base).
$targets = function (string $style, array $on) use ($botCtx, $raiseAttack) {
    $raiseAttack(1, 'myGroundArena-0');
    SWUBotSetDisabledFeatures($on);
    $ctx = $botCtx($style); $out = [];
    foreach ($ctx['actions'] as $i => $a) $out[strval($a['cardID'])] = SWUBotScoreAction($ctx, $a, $i);
    SWUBotSetDisabledFeatures([]);
    return $out;
};

// 1. BEHIND on power (their 3 + 4 = 7 against my 4): the kill must beat the base swing.
$board(['SOR_095', 'SOR_164']);                                        // Battlefield Marine 2/3/3, Wampa 4/4/5
$off = $targets('midrange', []);
$check(($off['theirBase-0'] ?? 0) > ($off['theirGroundArena-0'] ?? 0),
    'fixture: the shipped bot prefers the BASE over killing a cheap 3-power unit');
$on = $targets('midrange', $ON);
$check(($on['theirGroundArena-0'] ?? 0) > ($on['theirBase-0'] ?? 0),
    'mgtrade: while behind, killing the 3-power unit beats hitting the base');
$check(abs(($on['theirBase-0'] ?? 0) - ($off['theirBase-0'] ?? 0)) < 1e-9, 'mgtrade leaves the base value alone');

// 2. The bonus reads POWER, not cost. Neel costs 1 LESS than the Marine, so a cost proxy would separate them by
// 0.9; mgtrade separates them by 1.2 the other way, because a 1-power body is not a threat (owner A5b).
$gain3 = ($on['theirGroundArena-0'] ?? 0) - ($off['theirGroundArena-0'] ?? 0);
$board(['ASH_248', 'SOR_164']);                                        // Neel 1/1/4: a body, not a threat
$onNeel = $targets('midrange', $ON); $offNeel = $targets('midrange', []);
$gain1 = ($onNeel['theirGroundArena-0'] ?? 0) - ($offNeel['theirGroundArena-0'] ?? 0);
$check($gain3 > $gain1, 'mgtrade: killing a 3-power threat is worth more than killing a 1-power one');
$check(($onNeel['theirBase-0'] ?? 0) > ($onNeel['theirGroundArena-0'] ?? 0),
    'mgtrade still swings at the base rather than killing a 1-power body');

// 2b. Cost and power pulling in OPPOSITE directions, on one board: Grand Moff Tarkin costs twice the Marine and
// hits for a third less. The cost proxy ranks Tarkin the better target; the threat term must move the MARINE more.
$board(['SOR_095', 'SOR_084']);                                        // Marine 2/3/3, Tarkin 4/2/3 — their 5 power vs my 4
$cOff = $targets('midrange', []); $cOn = $targets('midrange', $ON);
$check(($cOff['theirGroundArena-1'] ?? 0) > ($cOff['theirGroundArena-0'] ?? 0),
    'fixture: the cost proxy ranks the expensive 2-power unit above the cheap 3-power one');
$gMarine = ($cOn['theirGroundArena-0'] ?? 0) - ($cOff['theirGroundArena-0'] ?? 0);
$gTarkin = ($cOn['theirGroundArena-1'] ?? 0) - ($cOff['theirGroundArena-1'] ?? 0);
$check($gMarine > $gTarkin, 'mgtrade pays for POWER: the cheaper but harder-hitting unit gains more');

// 3. AHEAD on power: nothing changes, the seat races.
$board(['ASH_248']);                                                   // their 1 power against my 4
$aOff = $targets('midrange', []); $aOn = $targets('midrange', $ON);
$check($aOff == $aOn, 'mgtrade: ahead on board, every target keeps its value');

// 4. Scope: midrange only, and inert by default.
$board(['SOR_095', 'SOR_164']);
$check($targets('softaggro', $ON) == $targets('softaggro', []), 'mgtrade does not touch a soft aggro seat');
$check($targets('softcontrol', $ON) == $targets('softcontrol', []), 'mgtrade does not touch a soft control seat');
$check($targets('hardcontrol', $ON) == $targets('hardcontrol', []), 'mgtrade does not touch a hard control seat');
$check($targets('midrange', []) == $off, 'mgtrade is inert by default');

bot_test_finish();
