<?php
// SHD_196
// Cost 2 - Grogu - Irresistible - [Cunning,Heroism] - Power 0 - HP 5
// Text: Action [exhaust]: Exhaust an enemy unit.

// ─── SHD_196 Grogu ────────────────────────────────────────────────────────────
// Action [exhaust]: Exhaust an enemy unit.
$unitActionCostKind["SHD_196"] = 'exhaust';

$unitAbilities["SHD_196"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
    SWUQueueAfterAction($player);
};
