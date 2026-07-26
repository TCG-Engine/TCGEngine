<?php
// ASH_067
// Cost 4 - Get Lost - [Vigilance,Heroism]
// Text: Defeat an upgraded non-leader unit.

$whenPlayedAbilities["ASH_067:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !IsLeaderUnit($o) && _SWUIsUpgraded($o)) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Defeat_an_upgraded_non-leader_unit", "DEFEAT_UNIT");
};
