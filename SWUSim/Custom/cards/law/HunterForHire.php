<?php
// LAW_156
// Cost 3 - Hunter For Hire - [Command] - Power 4 - HP 4
// Text: Action [defeat a friendly Credit token]: Take control of this unit. Any player may use this ability.

$anyPlayerUnitActions["LAW_156"] = true;

// LAW_156 Hunter For Hire — "Action [defeat a friendly Credit token]: Take control of this unit. Any
// player may use this ability." No exhaust ('none' cost kind); the closure pays the Credit-token cost
// (the ACTING player's) then takes control. Affordability (a friendly Credit token) is in
// SWUUnitActionAffordable; the opponent-offering is in SWUComputeActionsData.
$unitActionCostKind["LAW_156"] = 'none';

$unitAbilities["LAW_156"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $credits = SWUUsableCreditTokenMzIDs(intval($player));   // the acting player's Credit tokens
    if (empty($credits)) { SWUAfterAction(intval($player)); return; } // affordability-gated; defensive
    SWUDefeatCreditToken($credits[0]);                       // cost: defeat a friendly Credit token
    SWUTakeControlOfUnit(intval($player), $mzID);            // effect: take control of this unit
    SWUAfterAction(intval($player));
};
