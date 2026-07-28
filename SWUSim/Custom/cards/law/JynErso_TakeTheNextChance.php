<?php
// LAW_067
// Cost 2 - Jyn Erso - Take the Next Chance - [Command,Cunning,Heroism] - Power 2 - HP 2
// Text: When Played: Either give an Experience token to a unit or exhaust a unit.

// LAW_067 Jyn Erso — When Played: either give an Experience token to a unit OR exhaust a unit.
$whenPlayedAbilities["LAW_067:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "GiveExperience&Exhaust", 1, "Give_an_Experience_token_to_a_unit_or_exhaust_a_unit");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_067#0", 1);
};

$customDQHandlers["LAW_067#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'Exhaust') {
        $units = SWUAllUnits();
        if (empty($units)) return;
        SWUQueueChooseTarget(intval($player), $units, "Exhaust_a_unit", "EXHAUST_UNIT");
    } else {
        GiveTokenUpgrade($player, '', [
            'traits' => [], 'friendlyOnly' => false,
            'prompt' => "Give_an_Experience_token_to_a_unit",
        ]);
    }
};
