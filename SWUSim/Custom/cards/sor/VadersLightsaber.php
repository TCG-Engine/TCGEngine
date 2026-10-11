<?php
// SOR_136
// Cost 2 - Vader's Lightsaber - [Aggression,Villainy] - Upgrade Power 3 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / When Played: If attached unit is Darth Vader, you may deal 4 damage to a ground unit.

// SOR_136 Vader's Lightsaber — When Played (as upgrade): If attached unit is Darth Vader, you
// may deal 4 damage to a ground unit. $mzID = host unit mzID (WhenPlayed fallback, like SOR_053).
$whenPlayedAbilities["SOR_136:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (SWUObjectTitle($host) !== 'Darth Vader') return;
    SWUQueueMayChooseTarget(intval($player), array_merge(
        // ⚠ UNQUALIFIED pool = the WHOLE table, so the own side is 'team*', not 'my*': `their*` is the
        // OPPONENT fan-out and excludes a teammate, so my*+their* leaves a teammate's units in NEITHER
        // list. 'team*' degrades to 'my*' outside a team game (Premier byte-identical).
        ZoneSearch('teamGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ), 'Deal_4_damage_to_a_ground_unit?', 'Choose_a_ground_unit_to_deal_4_damage', 'DEAL_UNIT_DAMAGE|4');
};
