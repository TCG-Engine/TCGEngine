<?php
// JTL_086
// Cost 3 - Wingman Victor Three - Backstabber - [Command,Villainy] - Power 4 - HP 3 - Upgrade Power 1 - Upgrade HP 1
// Text: / Piloting [1 resource Command Villainy] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: You may give an Experience token to another unit.

// JTL_086 Wingman Victor Three (pilot) — When played as an upgrade: You may give an Experience token to
// another unit.
$whenPlayedAsUpgradeAbilities["JTL_086:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $hostUid = SWUObjUID($host, 0);
    $units = [];
    foreach (array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && intval($o->UniqueID ?? 0) !== $hostUid) $units[] = $mz;
    }
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Give_an_Experience_token_to_another_unit", "Choose_a_unit", "GIVE_EXPERIENCE|1");
};
