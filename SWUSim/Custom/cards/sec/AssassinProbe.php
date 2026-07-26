<?php
// SEC_263
// Cost 5 - Assassin Probe - Power 4 - HP 4
// Text: When Defeated: Deal 1 damage to each exhausted enemy ground unit.

// SEC_263 Assassin Probe — When Defeated: deal 1 to each exhausted enemy ground unit.
$whenDefeatedAbilities["SEC_263:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $uids = [];
    foreach (ZoneSearch("theirGroundArena", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 0) $uids[] = intval($o->UniqueID);
    }
    foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player)); }
};
