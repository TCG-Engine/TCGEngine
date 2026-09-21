<?php
// SHD_254
// Cost 1 - Bounty Guild Initiate - Power 1 - HP 2
// Text: When Played: If you control another Bounty Hunter unit, you may deal 2 damage to a ground unit.

// ─── SHD_254 Bounty Guild Initiate ────────────────────────────────────────────
// When Played: If you control ANOTHER Bounty Hunter unit, you may deal 2 damage to a ground unit.
$whenPlayedAbilities["SHD_254:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $gate = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID
            && HasTrait($u->CardID ?? '', 'Bounty Hunter')) { $gate = true; break; }
    }
    if (!$gate) return;
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
