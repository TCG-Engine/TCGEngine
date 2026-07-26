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
    global $playerID;
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueMayChooseTarget($opp, $tg, "Give_an_Experience_token_to_a_unit?", "Choose_a_unit", "GIVE_EXPERIENCE|1");
    // leave $playerID = $opp
};
