<?php
// JTL_066
// Cost 3 - Trace Martez - Trusting Sister - [Vigilance] - Power 2 - HP 5 - Upgrade Power 1 - Upgrade HP 2
// Text: / Piloting [1 resource Vigilance] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / Attached unit gains: "On Attack: You may heal 2 total damage from any number of units."

// JTL_066 Trace Martez (pilot) — granted "On Attack: You may heal 2 total damage from any number of
// units." (Implemented as heal up to 2 from one chosen unit — the common case.)
$onAttackAbilities["JTL_066:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Heal_2_from_a_unit", "Choose_a_unit_to_heal", "HEAL_TARGET|2");
};
