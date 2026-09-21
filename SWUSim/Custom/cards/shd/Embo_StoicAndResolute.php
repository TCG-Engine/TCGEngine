<?php
// SHD_059
// Cost 3 - Embo - Stoic and Resolute - [Vigilance] - Power 3 - HP 4
// Text: When this unit completes an attack: If the defender was defeated, heal up to 2 damage from a unit.

// ─── SHD_059 Embo ─────────────────────────────────────────────────────────────
// When this unit completes an attack: If the defender was defeated, heal up to 2 damage from a unit.
$onAttackEndAbilities["SHD_059:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GetSWUVar('SWU_LAST_DEFENDER_DEFEATED', '') !== '1') return;
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Heal_up_to_2_from_a_unit?", "Heal_up_to_2_from_a_unit", "HEAL_TARGET|2");
};
