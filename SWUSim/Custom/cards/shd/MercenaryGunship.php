<?php
// SHD_256
// Cost 2 - Mercenary Gunship - Power 3 - HP 2
// Text: Action [4 resources]: Take control of this unit. Any player may use this ability.

// SHD_256 Mercenary Gunship — "Action [4 resources]: Take control of this unit. Any player may use this
// ability." Same any-player take-control seam as LAW_156, but the cost is 4 resources (paid generically
// by SWUUnitAction via $unitActionResourceCosts, so 'none' base cost kind — no exhaust, repeatable).
// Affordability (4 ready resources) is the generic check in SWUUnitActionAffordable; the opponent-offering
// is in SWUComputeActionsData.
$anyPlayerUnitActions["SHD_256"] = true;

$unitActionCostKind["SHD_256"] = 'none';

$unitActionResourceCosts["SHD_256"] = 4;

$unitAbilities["SHD_256"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUTakeControlOfUnit(intval($player), $mzID);            // resource cost already paid by SWUUnitAction
    SWUAfterAction(intval($player));
};
