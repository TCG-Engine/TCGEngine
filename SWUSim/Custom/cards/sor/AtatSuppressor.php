<?php
// SOR_039
// Cost 8 - AT-AT Suppressor - [Vigilance,Villainy] - Power 8 - HP 8
// Text: When Played: Exhaust all ground units.

// SOR_039 AT-AT Suppressor — When Played: Exhaust all ground units (both players).
$whenPlayedAbilities["SOR_039:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    foreach (array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        OnExhaustCard($player, $mz);
    }
};
