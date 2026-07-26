<?php
// LAW_079
// Cost 5 - K-2SO - Locking the Vault - [Aggression,Cunning,Heroism] - Power 3 - HP 5
// Text: Ambush / On Attack: You may deal 3 damage to a damaged ground unit.

// LAW_079 K-2SO — Ambush + On Attack: you may deal 3 damage to a damaged ground unit.
$onAttackAbilities["LAW_079:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_3_to_a_damaged_ground_unit?", "Choose_a_damaged_ground_unit", "DEAL_UNIT_DAMAGE|3");
};
