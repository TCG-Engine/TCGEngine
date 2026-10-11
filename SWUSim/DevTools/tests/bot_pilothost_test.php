<?php
// Kill the pilot host before the pilot leader lands (proposal 'pilothost', 2026-10-07 gap screen). ⚠ Needs an owner ruling before it
// ships: does "removal on his pilots" (Krennic Blue plan vs Boba Blue, 2026-10-06) mean killing the HOSTS before he deploys?
// FOUND 2026-10-07 diagnosing Krennic vs Boba Fett (JTL) Blue (.claude/tmp/diag_boba): Boba (JTL_009: "Deploy this leader as an upgrade on
// a friendly Vehicle unit without a Pilot on it … Attached unit is a leader unit") piloted a ship in 80 of 80 Blue losses, and a leader
// unit is out of reach of No Glory, Chimaera and Pre Vizsla. When Boba had to deploy as a ground unit, Blue won 7 of 7; as a pilot, 11 of
// 91. boba-blue.krennic-blue s031 R5: "Chimaera defeated P1's Marrok", then "Boba Fett was attached as a pilot to P1's Droid Missile
// Platform" — Marrok and the Platform both valued 3, and the tie went to the ground Sentinel. The ship then hit for 8 three rounds running.
// The lever: while an enemy pilot leader is undeployed, its Epic Action unused and within one resource of its threshold, each enemy Vehicle
// without a Pilot is worth +SWU_BOT_PILOT_HOST_PREMIUM / (number of such Vehicles) more dead — priced from the OPPONENT's view only.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_pilothost_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
$check(in_array('pilothost', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-pilothost') === ['try:pilothost'], 'pilothost is a switchable proposal');
// Krennic Blue (LAW_008 on ASH_019) casts Lost and Forgotten (LAW_133); Boba (JTL_009 on ASH_019) with $res resources has Marrok (ASH_030,
// ground Sentinel, cost 3) and a Droid Missile Platform (JTL_162, space Vehicle, cost 3).
$board = function (int $res, bool $epicUsed = false) use ($build) {
    $build(function ($b) use ($res, $epicUsed) {
        $b->MyLeader('LAW_008', false); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LAW_133');
        $b->TheirLeader('JTL_009', false, false, $epicUsed); $b->TheirBase('ASH_019'); $b->FillResourcesForPlayer(2, 'SOR_095', $res);
        $b->WithGroundUnitForPlayer(2, 'ASH_030', true); $b->WithSpaceUnitForPlayer(2, 'JTL_162', true);
    });
};
$target = function (array $variant) use ($act, $botCtx, &$gameName) {
    $act(1, 10002, 'myHand-0!FSM!');
    $ctx = $botCtx('softcontrol');
    $p = SWUBotHeuristicChoose('softcontrol', $ctx['actions'], SWUBotLegalActions($gameName, 1), implode(',', array_map(fn($x) => 'try-' . substr($x, 4), $variant)));
    return [strval($ctx['tooltip']), strval($p['cardID'] ?? 'null')];
};
$board(5);
[$tip, $p] = $target([]);
$check(stripos($tip, 'Defeat') !== false, 'fixture: Lost and Forgotten asks what to defeat; got ' . $tip);
$check($p === 'theirGroundArena-0', 'premise: today the tie goes to Marrok; got ' . $p);
$board(5);
[, $p] = $target(['try:pilothost']);
$check($p === 'theirSpaceArena-0', '@try-pilothost: with Boba one resource from deploying, the pilot host goes; got ' . $p);
// Boba three resources away: no premium.
$board(3);
[, $p] = $target(['try:pilothost']);
$check($p === 'theirGroundArena-0', '@try-pilothost: Boba three resources away — unchanged; got ' . $p);
// Boba's Epic Action already used: he cannot deploy again — no premium.
$board(5, true);
[, $p] = $target(['try:pilothost']);
$check($p === 'theirGroundArena-0', '@try-pilothost: Epic Action used — unchanged; got ' . $p);
// Never priced from the pilot's own side: Boba's controller does not value its own host more.
$board(5);
$GLOBALS['SWUBotViewerSeat'] = 2; SWUBotSetDisabledFeatures(['try:pilothost']);
$own = _SWUBotPilotHostPremium(SWUBotViewForMz(2, 'mySpaceArena-0'));
SWUBotSetDisabledFeatures([]); unset($GLOBALS['SWUBotViewerSeat']);
$check($own === 0.0, '@try-pilothost: the host\'s own controller adds nothing; got ' . json_encode($own));

bot_test_finish();
