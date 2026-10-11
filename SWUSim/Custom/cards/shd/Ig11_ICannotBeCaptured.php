<?php
// SHD_170
// Cost 5 - IG-11 - I Cannot Be Captured - [Aggression] - Power 6 - HP 5
// Text: If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit instead. / On Attack: You may deal 3 damage to a damaged ground unit.

// ─── SHD_170 IG-11 ────────────────────────────────────────────────────────────
// (Capture-replacement handled in DoCaptureUnit.) On Attack: You may deal 3 damage to a damaged ground unit.
$onAttackAbilities["SHD_170:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_3_to_a_damaged_ground_unit?", "Deal_3_damage_to_a_damaged_ground_unit", "DEAL_UNIT_DAMAGE|3");
};
