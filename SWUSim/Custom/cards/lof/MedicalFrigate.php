<?php
// LOF_250
// Cost 4 - Medical Frigate - [Heroism] - Power 3 - HP 6
// Text: On Attack: You may heal 2 damage from another unit.

// LOF_250 Medical Frigate — On Attack: may heal 2 damage from another unit.
$onAttackAbilities["LOF_250:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Heal_2_from_another_unit?", "Choose_a_unit", "HEAL_TARGET|2");
};
