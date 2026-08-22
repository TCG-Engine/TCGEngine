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
    global $playerID; $playerID = intval($player);
    // "AN opponent may give…" — the controller chooses WHO gets the option.
    // ⚠ Eligibility is gated on the BOARD-WIDE unit pool, NOT per-opponent. The thing the chosen player
    // does is "give an Experience token to A UNIT", and that pool is board-wide and IDENTICAL for every
    // opponent — so "opponents who can do something" (the tempting answer) would filter nobody in a game
    // with any unit at all, and would wrongly filter EVERYONE in a game with none. Gate once, globally.
    if (empty(SWUAllUnits())) return;                      // no unit anywhere ⇒ no offer, no prompt
    SWUQueueChooseOpponent(intval($player), 'TS26_54#1',
        "Choose_an_opponent_to_give_an_Experience_token");
};

$customDQHandlers["TS26_54#1"] = function($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    // The chosen opponent is the giver; GiveTokenUpgrade sets/leaves $playerID = $opp.
    GiveTokenUpgrade($opp, '', [
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => "Choose_a_unit",
        'question'     => "Give_an_Experience_token_to_a_unit?",
    ]);
};
