<?php
// SEC_204
// Cost 4 - Blue Ace - Colorful Racer - [Cunning,Heroism] - Power 4 - HP 5
// Text: Ambush / On Attack: Ready an exhausted enemy unit.

// SEC_204 Blue Ace — Ambush + On Attack: ready an exhausted enemy unit (mandatory; 1 target auto-resolves).
$onAttackAbilities["SEC_204:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Ready_an_exhausted_enemy_unit", "READY_UNIT");
};
