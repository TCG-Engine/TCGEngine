<?php
// ASH_112
// Cost 6 - Luke Skywalker - Answering the Call - [Command,Heroism] - Power 5 - HP 5
// Text: Restore 1 / When Played: If you control at least 4 units, deal 3 damage to each enemy unit.

// ASH_112 Luke Skywalker — Restore 1 (keyword) + When Played: if you control at least 4 units, deal 3
// damage to each enemy unit.
$whenPlayedAbilities["ASH_112:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $friendly++; }
    if ($friendly < 4) return;
    // Snapshot enemy UIDs, then deal 3 to each (defeats resolve as units are processed).
    $uids = [];
    foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 3, intval($player));
    }
};
