<?php
// ASH_092
// Cost 4 - Foundling Rescue - [Vigilance]
// Text: You may defeat a unit with 2 or less remaining HP. Create a Mandalorian token.

$whenPlayedAbilities["ASH_092:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 2) $targets[] = $mz;
    }
    if (!empty($targets)) SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_unit_with_2_or_less_remaining_HP?", "Choose_a_unit", "DEFEAT_UNIT");
    SWUCreateUnitToken(intval($player), 'ASH_T01');   // create the Mandalorian regardless
};
