<?php
// HMW_270 Wild Space Wanderer — Unit (Space) 3/2, cost 3, no aspects, Fringe/Vehicle/Fighter.
// "When Played: You may defeat an upgrade on a base."
// Upgrades on units are not legal; either player's base is. Offered as subcard mzIDs ("<base>.uN").

$whenPlayedAbilities["HMW_270:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = array_values(array_filter(SWUGetUpgradeSubcardMzIDs(''),
        fn($mz) => preg_match('/Base-\d+\.u\d+$/', $mz) === 1));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Defeat_an_upgrade_on_a_base?", "Choose_an_upgrade_on_a_base", "HMW_270#0");
};

$customDQHandlers["HMW_270#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUDefeatUpgradeByMzID(intval($player), strval($lastDecision), false);
    DecisionQueueController::CleanupRemovedCards();
};
