<?php
// LOF_071
// Cost 7 - Grappling Guardian - [Vigilance] - Power 3 - HP 9
// Text: When Played: You may defeat a space unit with 6 or less remaining HP.

// LOF_071 Grappling Guardian — When Played: may defeat a space unit with 6 or less remaining HP.
$whenPlayedAbilities["LOF_071:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits(null, SpaceArena) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 6) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_space_unit_with_6_or_less_HP?", "Choose_a_space_unit", "DEFEAT_UNIT");
};
