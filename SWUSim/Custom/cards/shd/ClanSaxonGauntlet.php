<?php
// SHD_035
// Cost 6 - Clan Saxon Gauntlet - [Villainy,Vigilance] - Power 4 - HP 5
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When this unit is attacked: You may give an Experience token to a unit (before damage is dealt).

// ─── SHD_035 Clan Saxon Gauntlet ──────────────────────────────────────────────
// Sentinel (auto) + When this unit is attacked (On Defense): You may give an Experience token to a unit
// (before damage). The On Defense combat-pause is automatic (OnDefenseTrigger sets SWU_PENDING_DEF_REACTION).
$onDefenseAbilities["SHD_035:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_an_Experience_token_to_a_unit?", "Give_Experience_to_a_unit", "GIVE_EXPERIENCE|1");
};
