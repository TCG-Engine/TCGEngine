<?php
// SOR_097
// Cost 3 - Admiral Ackbar - Brilliant Strategist - [Command,Heroism] - Power 1 - HP 4
// Text: Restore 1 (When this unit attacks, heal 1 damage from your base.) / When Played: You may deal damage to a unit equal to the number of units you control in its arena.

// SOR_097 Admiral Ackbar — When Played: "You may deal damage to a unit equal to the number of units
// you control in its arena." (Restore 1 is auto-wired.) The amount depends on the CHOSEN target's
// arena, so it is computed at resolution time in a bespoke handler.
$whenPlayedAbilities["SOR_097:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_equal_to_your_units_in_its_arena",
        "Choose_a_unit_to_damage", "SOR_097#0");
};

$customDQHandlers["SOR_097#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    // "units you control in its arena" — count the player's own units in the target's arena.
    $myZone = (strpos($lastDecision, 'SpaceArena') !== false) ? "mySpaceArena" : "myGroundArena";
    $count = count(ZoneSearch($myZone, AnyUnitFilter));
    if ($count <= 0) return;
    SWUDealDamageToUnit($lastDecision, $count, intval($player));
};
