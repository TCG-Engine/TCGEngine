<?php
// LOF_064
// Cost 3 - Tauntaun - [Vigilance] - Power 3 - HP 3
// Text: When Defeated: You may give a Shield token to a damaged non-Vehicle unit.

// LOF_064 Tauntaun — When Defeated: may give a Shield token to a damaged non-Vehicle unit.
$whenDefeatedAbilities["LOF_064:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Damage ?? 0) > 0 && !HasTrait($o->CardID ?? '', 'Vehicle')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_Shield_to_a_damaged_non-Vehicle_unit?", "Choose_a_unit", "GIVE_SHIELD");
};
