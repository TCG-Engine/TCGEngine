<?php
// SOR_038
// Cost 7 - Count Dooku - Darth Tyranus - [Vigilance,Villainy] - Power 5 - HP 4
// Text: Shielded (When you play this unit, give him a Shield token.) / When Played: You may defeat a unit with 4 or less remaining HP.

// SOR_038 Count Dooku — When Played: you may defeat a unit with 4 or less remaining HP.
$whenPlayedAbilities["SOR_038:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 4) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Defeat_a_unit_with_4_or_less_remaining_HP?", "Defeat_a_unit_with_4_or_less_remaining_HP", "DEFEAT_UNIT");
};
