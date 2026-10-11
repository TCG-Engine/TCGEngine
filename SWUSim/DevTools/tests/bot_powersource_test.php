<?php
// Part 22 'powersource' — a friendly unit chosen to DEAL damage equal to its power is the strongest one, not the cheapest.
// Owner report 2026-10-03, game 1438045: "when i attack with my imperial armored commando, it chooses to deploy then power
// strike with the Spy which makes no sense as it has 0 power". LAW_008 Director Krennic, When Deployed: "Another friendly
// unit deals damage equal to its power to an enemy unit." The source prompt ("Choose_another_friendly_unit_to_deal_damage_
// equal_to_its_power.") read as "deal damage" to a FRIENDLY unit — self-harm — so each candidate was priced as a loss and
// the cheapest body won: the Spy token (0 power) at -0.5 over the Imperial Armored Commando (4 power) at -4.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_powersource_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-powersource') === ['powersource'], 'powersource is switchable');

// Walks the bot through every prompt after $first; returns [[tooltip, pick, cardID of a friendly pick]].
$walk = function (string $first, int $mode, string $variant = '') use (&$gameName, $act) {
    $act(1, $mode, $first);
    $out = [];
    for ($i = 0; $i < 8; $i++) {
        $l = SWUBotLegalActions($gameName, 1);
        if (($l['kind'] ?? '') !== 'decision') break;
        $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
        $id = strval($p['cardID'] ?? 'PASS');
        global $playerID; $s = $playerID; $playerID = 1; $o = str_starts_with($id, 'my') ? GetZoneObject($id) : null; $playerID = $s;
        $out[] = [strval($l['decisionTooltip'] ?? ''), $id, $o ? strval($o->CardID) : ''];
        $act(1, intval($p['mode'] ?? 10001), $id);
    }
    return $out;
};
$sourceOf = fn($picks) => array_values(array_filter($picks, fn($p) => stripos($p[0], 'friendly_unit_to_deal') !== false))[0][2] ?? null;

// A) THE REPORTED BOARD (game 1438045, seats swapped so the bot is seat 1), right after the human's Commando traded Shields
// with the bot's: the bot has a ready Spy token (0/2) and an exhausted Imperial Armored Commando (4/3, no Shield); the
// human has an exhausted Commando with Craving Power (6/5) and a deployed General Grievous. The bot deploys Krennic.
$board = function ($b) {
    $b->MyLeader('LAW_008'); $b->FillResourcesForPlayer(1, 'SOR_095', 7, false);
    $b->WithGroundUnitForPlayer(1, 'SEC_T01', true); $b->WithGroundUnitForPlayer(1, 'ASH_048', false);
    $b->TheirLeader('HMW_008', true, true, true, 'unit');
    $b->WithGroundUnitForPlayer(2, 'ASH_048', false); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('LOF_091', 2)]);
    $b->FillResourcesForPlayer(2, 'SOR_095', 7);
};
$build($board);
$hp0 = array_sum(array_map(fn($u) => $u['remaining'], SWUBotUnits(2)));
$picks = $walk('myLeader-0!CustomInput!DeployLeader:Unit', 10001);
$check($sourceOf($picks) === 'ASH_048', 'A: Krennic\'s When Deployed strikes with the Commando (4 power), not the Spy (0); got ' . json_encode($picks));
$check($hp0 - array_sum(array_map(fn($u) => $u['remaining'], SWUBotUnits(2))) === intval(CardPower('ASH_048')),
    'A: …and the strike lands: ' . CardPower('ASH_048') . ' damage on an enemy unit');
$build($board);
$picksOff = $walk('myLeader-0!CustomInput!DeployLeader:Unit', 10001, 'no-powersource');
$check($sourceOf($picksOff) === 'SEC_T01', 'A @no-powersource: the Spy (the reported line); got ' . json_encode($picksOff));

// B) SOR_127 Strike True, the family's other source prompt: a 0-power Spy and a 3-power Battlefield Marine — the Marine.
$build(function ($b) { $b->MyLeader('SOR_014'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'SOR_127');
    $b->WithGroundUnitForPlayer(1, 'SEC_T01', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$picks = $walk('myHand-0!FSM!', 10002);
$check($sourceOf($picks) === 'SOR_095', 'B: Strike True deals with the Marine (3 power), not the Spy; got ' . json_encode($picks));
