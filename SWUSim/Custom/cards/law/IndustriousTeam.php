<?php
// LAW_124
// Cost 8 - Industrious Team - [Vigilance] - Power 4 - HP 7
// Text: When Played: You may defeat a non-leader unit with 4 or less remaining HP.

// LAW_124 Industrious Team — When Played: you may defeat a non-leader unit with 4 or less remaining HP.
$whenPlayedAbilities["LAW_124:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 4) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_(4_or_less_remaining_HP)?", "Choose_a_unit", "DEFEAT_UNIT");
};
