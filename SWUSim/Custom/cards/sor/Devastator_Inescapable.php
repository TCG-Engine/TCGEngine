<?php
// SOR_090
// Cost 10 - Devastator - Inescapable - [Command,Villainy] - Power 10 - HP 10
// Text: Sentinel / Overwhelm / When Played: You may deal damage to a unit equal to the number of resources you control.

// SOR_090 Devastator — When Played: you may deal damage to a unit equal to resources you control.
$whenPlayedAbilities["SOR_090:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $n = SWUResourceCount(intval($player));
    if ($n <= 0) return;
    $targets = SWUAllUnits();
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_damage_equal_to_resources_you_control?", "Deal_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $n);
};
