<?php
// SHD_150
// Cost 4 - Koska Reeves - Loyal Nite Owl - [Heroism,Aggression] - Power 4 - HP 5
// Text: On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit.

// ─── SHD_150 Koska Reeves ─────────────────────────────────────────────────────
// On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit.
$onAttackAbilities["SHD_150:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self) || !_SWUIsUpgraded($self)) return;
    $targets = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_2_to_a_ground_unit?", "Deal_2_to_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
