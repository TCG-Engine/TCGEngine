<?php
// LOF_095
// Cost 2 - Lor San Tekka - Secret Keeper - [Command,Heroism] - Power 3 - HP 2
// Text: When Defeated: You may give an Experience token to a <uq> (unique) unit.

// LOF_095 Lor San Tekka — When Defeated: may give an Experience token to a unique unit.
$whenDefeatedAbilities["LOF_095:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && CardUnique($o->CardID ?? '')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_Exp_to_a_unique_unit?", "Choose_a_unique_unit", "GIVE_EXPERIENCE|1");
};
