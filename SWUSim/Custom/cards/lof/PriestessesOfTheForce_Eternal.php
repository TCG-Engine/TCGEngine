<?php
// LOF_072
// Cost 7 - Priestesses of the Force - Eternal - [Vigilance] - Power 6 - HP 8
// Text: When Played: You may use the Force (lose your Force token). If you do, give a Shield token to each of up to 5 units.

// LOF_072 Priestesses of the Force — When Played: may use the Force → give a Shield token to each of up
// to 5 units.
$whenPlayedAbilities["LOF_072:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_Shield_up_to_5_units?", "LOF_072#0");
};

$customDQHandlers["LOF_072#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    $max = min(5, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $targets), 1,
        tooltip: "Give_a_Shield_token_to_each_of_up_to_5_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_072#1", 1, dontSkipOnPass: 1);
};

$customDQHandlers["LOF_072#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        GiveShieldToken(intval($player), $mz);
    }
};
