<?php
// TWI_171
// Cost 2 - Grenade Strike - [Aggression]
// Text: Deal 2 damage to a unit. You may deal 1 damage to another unit in the same arena.

// TWI_171 Grenade Strike (event continuation) — deal 2 to the chosen unit, then may deal 1 to another
// unit in the SAME arena as the first target.
$customDQHandlers["TWI_171#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $first = GetZoneObject($lastDecision);
    if (SWUObjGone($first)) return;
    $firstUID = intval($first->UniqueID ?? 0);
    $isSpace = (strpos((string)$lastDecision, 'SpaceArena') !== false);
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    // Offer 1 damage to another unit in the same arena (either player), excluding the first target.
    $zones = $isSpace ? ["mySpaceArena", "theirSpaceArena"] : ["myGroundArena", "theirGroundArena"];
    $targets = [];
    foreach ($zones as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $firstUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_1_to_another_unit_in_the_same_arena", "Deal_1_damage_to_another_unit", "DEAL_UNIT_DAMAGE|1");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_171:0"] = function($player, $mzID = '') {
// Grenade Strike — "Deal 2 damage to a unit. You may deal 1 damage to another
                          // unit in the same arena."
            global $playerID;
            $playerID = intval($player);
            $targets = SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_unit", "TWI_171#0");
            return;
};
