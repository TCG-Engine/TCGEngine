<?php
// SOR_127
// Cost 3 - Strike True - [Command]
// Text: A friendly unit deals damage equal to its power to an enemy unit.

// SOR_127 Strike True — step 1: friendly dealer chosen ($lastDecision); collect
// enemy targets and carry the dealer mzID into step 2 via the handler param.
$customDQHandlers["SOR_127#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $friendlyMz = $lastDecision;
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Choose_an_enemy_unit", "SOR_127#1|" . $friendlyMz, 0);
};

// SOR_127 step 2: deal the dealer's current power to the chosen enemy ($lastDecision).
$customDQHandlers["SOR_127#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $fo = GetZoneObject($parts[0] ?? '');
    if (SWUObjGone($fo)) return;
    $power = intval(ObjectCurrentPower($fo));
    if ($power > 0) SWUDealDamageToUnit($lastDecision, $power, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_127:0"] = function($player, $mzID = '') {
// Strike True — "A friendly unit deals damage equal to its power to an enemy unit."
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly) || empty($enemy)) return; // needs both a dealer and a target
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_your_unit_to_deal_damage", "SOR_127#0");
            return;
};
