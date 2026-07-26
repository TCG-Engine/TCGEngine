<?php
// JTL_057
// Cost 1 - Astromech Pilot - [Vigilance] - Power 1 - HP 3 - Upgrade Power 1 - Upgrade HP 3
// Text: / Piloting [2 resources Vigilance] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: You may heal 2 damage from a unit.

// JTL_057 Astromech Pilot (pilot) — When played as an upgrade: You may heal 2 damage from a unit.
$whenPlayedAsUpgradeAbilities["JTL_057:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Heal_2_from_a_unit", "Choose_a_unit_to_heal", "HEAL_TARGET|2");
};
