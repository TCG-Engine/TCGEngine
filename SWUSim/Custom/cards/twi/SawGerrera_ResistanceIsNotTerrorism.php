<?php
// TWI_150
// Cost 6 - Saw Gerrera - Resistance is Not Terrorism - [Aggression,Heroism] - Power 4 - HP 8
// Text: Raid 2 / On Attack: If your base has 15 or more damage on it, deal 1 damage to each enemy ground unit.

// TWI_150 Saw Gerrera — "On Attack: If your base has 15 or more damage on it, deal 1 damage to each
// enemy ground unit." (Raid 2 is a keyword. Snapshot UIDs before dealing — the AoE discipline.)
$onAttackAbilities["TWI_150:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $base = GetBase(intval($player));
    if (empty($base) || !isset($base[0]) || intval($base[0]->Damage ?? 0) < 15) return;
    $uids = [];
    foreach (ZoneSearch("theirGroundArena", ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player));
    }
    // Combat owns the after-action.
};
