<?php
// TWI_164
// Cost 4 - Hevy - Staunch Martyr - [Aggression] - Power 4 - HP 4
// Text: Coordinate - Raid 2 (Gain this keyword while you control 3 or more units. This unit gets +2/+0 while attacking.) / When Defeated: Deal 1 damage to each enemy ground unit.

// TWI_164 Hevy — "Coordinate - Raid 2. When Defeated: Deal 1 damage to each enemy ground unit."
$whenDefeatedAbilities["TWI_164:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $uids = [];
    foreach (ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit']) as $emz) {
        $o = GetZoneObject($emz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? -1);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
