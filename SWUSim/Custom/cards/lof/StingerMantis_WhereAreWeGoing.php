<?php
// LOF_198
// Cost 5 - Stinger Mantis - Where Are We Going? - [Cunning,Heroism] - Power 4 - HP 6
// Text: When Played: You may deal 2 damage to an exhausted unit.

// LOF_198 Stinger Mantis — When Played: may deal 2 damage to an exhausted unit.
$whenPlayedAbilities["LOF_198:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) !== 1) $targets[] = $mz; // exhausted (Status != ready)
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_2_to_an_exhausted_unit?", "Choose_an_exhausted_unit", "DEAL_UNIT_DAMAGE|2");
};
