<?php
// STEPPED MZSPLITASSIGN ("total|targets|MODE|STEP") — added 2026-09-14 for HMW_036 Kelnacca, whose "for every 3
// resources paid, deal damage equal to this unit's power to an enemy unit" strikes are assigned in ONE divided
// prompt in steps of its power (CR 34.1: the instances resolve simultaneously). Guards the two server-side
// consumers of the new 4th segment:
//   1. SWUValidateDecisionAnswer refuses an amount that is not a whole number of steps;
//   2. the bot enumerator (SWUBotLegalActions) offers ONLY stepped answers, and the engine accepts them — a
//      trailing numeric segment used to fall through to the shared bridge, which mis-splits the param.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/stepped_split_assign_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotLegalActions.php';
$ids = fn($legal) => array_map(fn($a) => strval($a['cardID'] ?? ''), (array)($legal['actions'] ?? []));
$pending = function (int $seat) { foreach (GetDecisionQueue($seat) as $e) { if ($e !== null && empty($e->removed)) return $e; } return null; };

// Kelnacca (4/5, Command/Vigilance) on a Command base with an Aggression/Heroism leader: 4 + 2 (Vigilance) = 6.
// 12 resources: 6 to play it, 6 paid into its When Played → 2 strikes of 4 over two enemy units.
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 12);
    $b->WithCardInHandForPlayer(1, 'HMW_036');
    $b->WithGroundUnitForPlayer(2, 'SOR_046', true);   // Consular Security Force 3/7
    $b->WithGroundUnitForPlayer(2, 'LOF_168', true);   // Ravenous Rathtar 8/5
});
$act(1, 10002, 'myHand-0!FSM!');
$act(1, 100, '6');
$head = $pending(1);
$check($head !== null && $head->Type === 'MZSPLITASSIGN' && str_ends_with(strval($head->Param), '|ALL|4'),
    'fixture: the stepped assignment is pending — ' . json_encode($head->Param ?? null));

// 1. Validator.
$check(SWUValidateDecisionAnswer(1, 'theirGroundArena-0:8') === true, 'both strikes stacked on one unit is legal');
$check(SWUValidateDecisionAnswer(1, 'theirGroundArena-0:4,theirGroundArena-1:4') === true, 'one strike each is legal');
$check(SWUValidateDecisionAnswer(1, 'theirGroundArena-0:5,theirGroundArena-1:3') === false, 'amounts off the step are refused');
$check(SWUValidateDecisionAnswer(1, 'theirGroundArena-0:12') === false, 'over the pool is refused');

// 2. Bot enumerator.
$legal = SWUBotLegalActions($gameName, 1);
$cands = $ids($legal);
$check(($legal['decisionType'] ?? '') === 'MZSPLITASSIGN' && !empty($cands), 'the bot sees the stepped split with candidates');
$allStepped = !empty($cands);
foreach ($cands as $c) {
    foreach (explode(',', $c) as $pair) {
        $b = explode(':', $pair);
        if (count($b) !== 2 || intval($b[1]) % 4 !== 0) { $allStepped = false; break 2; }
    }
}
$check($allStepped, 'every bot candidate is a whole number of strikes — ' . json_encode(array_slice($cands, 0, 6)));
$check(count(array_filter($cands, fn($c) => SWUValidateDecisionAnswer(1, $c))) === count($cands), 'every bot candidate passes the validator');
$check(in_array('theirGroundArena-0:8', $cands, true) || in_array('theirGroundArena-1:8', $cands, true), 'stacking both strikes is among the candidates');
$act(1, 100, $cands[0] ?? '');
$e = $pending(1);
$check(($cands[0] ?? '') !== '' && ($e === null || $e->Type !== 'MZSPLITASSIGN'), 'the engine accepts the first bot candidate (' . ($cands[0] ?? '') . ')');

bot_test_finish();
