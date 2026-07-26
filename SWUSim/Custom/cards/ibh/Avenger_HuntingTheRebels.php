<?php
// IBH_072
// Cost 8 - Avenger - Hunting the Rebels - [Vigilance,Villainy] - Power 8 - HP 6
// Text: When Played: Deal 1 damage to each other unit (including friendly units).

// IBH_072 Avenger — When Played: deal 1 damage to each other unit (including friendly units). Resolve
// by UID so deaths/reindexing don't skip a target; exclude Avenger itself.
$whenPlayedAbilities["IBH_072:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $uids = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $uids[] = intval($o->UniqueID);
        }
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
