<?php
// LOF_223
// Cost 2 - Force Illusion - [Cunning]
// Text: Exhaust an enemy unit. A friendly unit gains Sentinel for this phase.

// LOF_223 Force Illusion — exhaust the chosen enemy unit, then a friendly unit gains Sentinel this phase.
$customDQHandlers["LOF_223#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $o->Status = 0; // exhaust the enemy unit
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_Sentinel_this_phase", "GRANT_PHASE_KEYWORD|SENTINEL^LOF_223");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_223:0"] = function($player, $mzID = '') {
// Force Illusion — "Exhaust an enemy unit. A friendly unit gains Sentinel for
                          // this phase."
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($enemy)) {
                // Two independent sentences: with no enemy to exhaust, still resolve the unconditional
                // Sentinel-grant clause (mirror of the LOF_223#0 handler's friendly-grant step).
                $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
                if (empty($friendly)) return;
                SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_Sentinel_this_phase", "GRANT_PHASE_KEYWORD|SENTINEL^LOF_223");
                return;
            }
            SWUQueueChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit", "LOF_223#0");
            return;
};
