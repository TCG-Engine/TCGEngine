<?php
// ASH_259
// Cost 1 - LEP Ratcatcher - Power 1 - HP 1
// Text: When Played: You may deal 1 damage to a ground unit.

// ASH_259 — LEP Ratcatcher: "When Played: Deal 1 damage to a unit." (any unit)
$whenPlayedAbilities["ASH_259:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", "myGroundArena&mySpaceArena&theirGroundArena&theirSpaceArena", 1, "Choose_a_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEAL_UNIT_DAMAGE|1", 1);
};

// ASH_259 LEP Ratcatcher — When Played: you may deal 1 damage to a ground unit.
$whenPlayedAbilities["ASH_259:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits(null, GroundArena);
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Deal_1_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|1");
};
