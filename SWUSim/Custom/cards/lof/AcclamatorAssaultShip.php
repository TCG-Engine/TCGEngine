<?php
// LOF_106
// Cost 7 - Acclamator Assault Ship - [Command,Command] - Power 5 - HP 8
// Text: On Attack: You may give another unit +5/+5 for this phase.

// LOF_106 Acclamator Assault Ship — On Attack: may give another unit +5/+5 for this phase.
$onAttackAbilities["LOF_106:0"] = function($player, $mzID) {
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
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_another_unit_+5/+5?", "Choose_a_unit", "APPLY_PHASE_BUFF|5|5|LOF_106");
};
