<?php
// SOR_099
// Cost 4 - Bright Hope - The Last Transport - [Command,Heroism] - Power 2 - HP 6
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Played: You may return a friendly non-leader ground unit to its owner's hand. If you do, draw a card.

// SOR_099 Bright Hope — When Played: You may return a friendly non-leader GROUND unit to
// hand. If you do, draw a card.
$whenPlayedAbilities["SOR_099:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player),
        ZoneSearch('myGroundArena', NonLeaderUnitFilter), // non-leader ground
        'Return_a_friendly_ground_unit_to_hand_(then_draw)?', 'Choose_a_friendly_ground_unit_to_return', 'SOR_099#0');
};

$customDQHandlers["SOR_099#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    if (SWUBounceUnit($player, $lastDecision)) DoDrawCard(intval($player), 1); // "If you do, draw"
};
