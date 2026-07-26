<?php
// LOF_242
// Cost 1 - Refugee of The Path - [Heroism] - Power 0 - HP 3
// Text: When Played: You may give a Shield token to a unit with Sentinel.

// LOF_242 Refugee of The Path — When Played: may give a Shield token to a unit with Sentinel.
$whenPlayedAbilities["LOF_242:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasKeyword_Sentinel($o)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_Shield_to_a_Sentinel_unit?", "Choose_a_Sentinel_unit", "GIVE_SHIELD");
};
