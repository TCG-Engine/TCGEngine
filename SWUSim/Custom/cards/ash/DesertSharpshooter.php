<?php
// ASH_170
// Cost 3 - Desert Sharpshooter - [Aggression] - Power 3 - HP 3
// Text: When Played: You may deal 2 damage to an upgraded ground unit.

// ASH_170 Desert Sharpshooter — When Played: you may deal 2 damage to an upgraded ground unit.
$whenPlayedAbilities["ASH_170:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && _SWUIsUpgraded($o)) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Deal_2_to_an_upgraded_ground_unit?", "Choose_an_upgraded_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
