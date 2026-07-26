<?php
// LOF_165
// Cost 5 - Asajj Ventress - Harden Your Heart - [Aggression] - Power 5 - HP 6
// Text: When Played/On Attack: Give another friendly Force unit +2/+0 for this phase.

// LOF_165 Asajj Ventress — When Played/On Attack: give another friendly Force unit +2/+0 for this phase.
$whenPlayedAbilities["LOF_165:0"] =
$onAttackAbilities["LOF_165:0"]   = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        if (TraitContains($o, 'Force')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_another_Force_unit_+2/+0?", "Choose_a_Force_unit", "APPLY_PHASE_BUFF|2|0|LOF_165");
};
