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
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_2_to_a_ground_unit?", "Deal_2_to_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
