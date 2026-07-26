<?php
// LAW_186
// Cost 2 - Enfys Nest's Helmet - [Aggression,Heroism] - Upgrade Power 0 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Attack: You may give another unit +3/+0 for this phase."

// LAW_186 Enfys Nest's Helmet — granted "On Attack: You may give another unit +3/+0 for this phase."
// (OnAttackFromUpgrade seam; $mzID = the attacking host. "Another" excludes the host.)
$onAttackAbilities["LAW_186:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_another_unit_+3/+0_this_phase?", "Choose_a_unit", "APPLY_PHASE_BUFF|3|0|LAW_186");
};
