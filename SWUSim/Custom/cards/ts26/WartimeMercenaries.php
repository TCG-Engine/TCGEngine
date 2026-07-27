<?php
// TS26_54
// Cost 4 - Wartime Mercenaries - [Command] - Power 5 - HP 5
// Text: When Defeated: An opponent may give an Experience token to a unit.

// TS26_54 Wartime Mercenaries — When Defeated: an opponent may give an Experience token to a unit.
// (Cross-player: queued via an intermediate CUSTOM so the opponent's pick validates under their frame.)
$whenDefeatedAbilities["TS26_54:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_54#0", 1);
};

$customDQHandlers["TS26_54#0"] = function($player, $parts, $lastDecision) {
    $opp = OtherPlayer(intval($player));
    // The opponent is the giver; GiveTokenUpgrade sets/leaves $playerID = $opp.
    GiveTokenUpgrade($opp, '', [
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => "Choose_a_unit",
        'question'     => "Give_an_Experience_token_to_a_unit?",
    ]);
};
