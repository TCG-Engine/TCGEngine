<?php
// SHD_158
// Cost 6 - Wild Rancor - [Aggression,Aggression] - Power 6 - HP 8
// Text: Overwhelm / When Played: Deal 2 damage to each other ground unit.

// ─── SHD_158 Wild Rancor ──────────────────────────────────────────────────────
// Overwhelm (auto) + When Played: Deal 2 damage to each OTHER ground unit (both sides). Resolve by UID so
// deaths/reindexing don't skip a target; exclude the Rancor itself (IBH_072 pattern, ground-only).
$whenPlayedAbilities["SHD_158:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $uids = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $uids[] = intval($o->UniqueID);
        }
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    }
};
