<?php
// SHD_150
// Cost 4 - Koska Reeves - Loyal Nite Owl - [Heroism,Aggression] - Power 4 - HP 5
// Text: On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit.

// ─── SHD_150 Koska Reeves ─────────────────────────────────────────────────────
// On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit.
$onAttackAbilities["SHD_150:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self) || !_SWUIsUpgraded($self)) return;
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_2_to_a_ground_unit?", "Deal_2_to_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
