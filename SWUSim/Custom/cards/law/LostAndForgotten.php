<?php
// LAW_133
// Cost 6 - Lost and Forgotten - [Vigilance]
// Text: Defeat a non-leader unit. If you do, heal 3 damage from your base.

// LAW_133 Lost and Forgotten — defeat the chosen non-leader unit; if it was defeated, heal 3 from base.
$customDQHandlers["LAW_133#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    SWUDefeatUnit(intval($player), $lastDecision);
    if (SWUFindMzByUID($uid) === null) OnHealBase(intval($player), intval($player), 3);   // "If you do"
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_133:0"] = function($player, $mzID = '') {
// Lost and Forgotten — "Defeat a non-leader unit. If you do, heal 3 damage from
                          // your base."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Defeat_a_non-leader_unit", "LAW_133#0");
            return;
};
