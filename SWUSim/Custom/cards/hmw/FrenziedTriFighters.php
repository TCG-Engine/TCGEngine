<?php
// HMW_249 Frenzied Tri-Fighters — Unit (Space) 5/3, cost 5, [Villainy], Separatist/Droid/Vehicle/Fighter.
// "When Played: You may defeat an upgrade that costs 3 or less."
// Any upgrade in play — on a unit of either side or on a base (Fortify). PRINTED cost; token upgrades
// cost 0 and are legal.

$whenPlayedAbilities["HMW_249:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = SWUGetUpgradeSubcardMzIDs('cost<=3');
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Defeat_an_upgrade_that_costs_3_or_less?", "Choose_an_upgrade_to_defeat", "HMW_249#0");
};

$customDQHandlers["HMW_249#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUDefeatUpgradeByMzID(intval($player), strval($lastDecision), false);
    DecisionQueueController::CleanupRemovedCards();
};
