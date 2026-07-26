<?php
// LOF_135
// Cost 4 - Scythe - Intimidating Silhouette - [Aggression,Villainy] - Power 3 - HP 5
// Text: On Attack: You may give another friendly Inquisitor unit +2/+0 for this phase.

// LOF_135 Scythe — On Attack: may give another friendly Inquisitor unit +2/+0 for this phase.
$onAttackAbilities["LOF_135:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        if (HasTrait($o->CardID ?? '', 'Inquisitor')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_another_Inquisitor_unit_+2/+0?", "Choose_an_Inquisitor", "APPLY_PHASE_BUFF|2|0|LOF_135");
};
