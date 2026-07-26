<?php
// JTL_044
// Cost 2 - Echo Base Engineer - [Vigilance,Heroism] - Power 2 - HP 3
// Text: When Played: You may give a Shield token to a damaged Vehicle unit.

// ── JTL_044 Echo Base Engineer — When Played: You may give a Shield token to a damaged Vehicle. ───────
$whenPlayedAbilities["JTL_044:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage) > 0 && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_a_Shield_to_a_damaged_Vehicle", "Give_a_Shield_token", "GIVE_SHIELD");
};
