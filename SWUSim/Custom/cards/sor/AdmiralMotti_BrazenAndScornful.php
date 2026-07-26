<?php
// SOR_226
// Cost 2 - Admiral Motti - Brazen and Scornful - [Villainy] - Power 1 - HP 1
// Text: When Defeated: You may ready a [Villainy] unit.

// SOR_226 Admiral Motti — "When Defeated: You may ready a [Villainy] unit."
// Single MZMAYCHOOSE over all Villainy units; READY_UNIT no-ops on a '-' decline.
$whenDefeatedAbilities["SOR_226:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $obj = GetZoneObject($mz);
        if (SWUObjGone($obj)) continue;
        if (strpos(CardAspect($obj->CardID) ?? '', 'Villainy') !== false) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Ready_a_Villainy_unit?", "Ready_a_Villainy_unit", "READY_UNIT");
};
