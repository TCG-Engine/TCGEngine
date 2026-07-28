<?php
// TWI_041
// Cost 4 - Lethal Crackdown - [Vigilance,Villainy]
// Text: Defeat a non-leader unit. Deal damage to your base equal to that unit's power.

// TWI_041 Lethal Crackdown (event continuation) — deal the chosen unit's power to your OWN base, then
// defeat it. (Snapshot power BEFORE defeat.)
$customDQHandlers["TWI_041#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $power = intval(ObjectCurrentPower($o));
    SWUDefeatUnit(intval($player), $lastDecision);
    if ($power > 0) SWUDealDamageToBase($power, intval($player), intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_041:0"] = function($player, $mzID = '') {
// Lethal Crackdown — "Defeat a non-leader unit. Deal damage to your base equal
                          // to that unit's power." (Snapshot power before defeat, in the continuation.)
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_(deal_its_power_to_your_base)", "TWI_041#0");
            return;
};
