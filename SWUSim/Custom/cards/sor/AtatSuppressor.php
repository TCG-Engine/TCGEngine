<?php
// SOR_039
// Cost 8 - AT-AT Suppressor - [Vigilance,Villainy] - Power 8 - HP 8
// Text: When Played: Exhaust all ground units.

// SOR_039 AT-AT Suppressor — When Played: Exhaust all ground units (both players).
$whenPlayedAbilities["SOR_039:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    foreach (array_merge(
        // ⚠ UNQUALIFIED pool = the WHOLE table, so the own side is 'team*', not 'my*': `their*` is the
        // OPPONENT fan-out and excludes a teammate, so my*+their* leaves a teammate's units in NEITHER
        // list. 'team*' degrades to 'my*' outside a team game (Premier byte-identical).
        ZoneSearch('teamGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        OnExhaustCard($player, $mz);
    }
};
