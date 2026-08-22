<?php
// ASH_083
// Cost 12 - Summa-verminoth - [Vigilance] - Power 15 - HP 15
// Text: Sentinel / On Attack: Defeat all other space units.

// ASH_083 Summa-verminoth — Sentinel (keyword) + On Attack: defeat all OTHER space units (both players').
$onAttackAbilities["ASH_083:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $uids = [];
    foreach (GetLiveSeatsArray() as $p) {
        foreach (GetSpaceArena($p) as $u) {
            if ($u !== null && empty($u->removed) && intval($u->UniqueID ?? -1) !== $selfUID) $uids[] = intval($u->UniqueID);
        }
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
    }
};
