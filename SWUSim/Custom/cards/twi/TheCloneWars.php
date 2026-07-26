<?php
// TWI_125
// Cost 2 - The Clone Wars - [Command]
// Text: Pay any number of resources. Create that many Clone Trooper tokens. Each opponent creates that many Battle Droid tokens.

// TWI_125 The Clone Wars continuation — pay X resources, create X Clone Troopers for the caster and
// X Battle Droids for each opponent.
$customDQHandlers["TWI_125#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $x = intval($lastDecision);
    if ($x <= 0) return;
    if (!SWUExhaustResources(intval($player), $x)) return; // NUMBERCHOOSE was capped at ready
    SWUCreateUnitTokens(intval($player), 'TWI_T02', $x);                  // caster's Clone Troopers
    // Twin Suns (Phase 3): each opponent creates X Battle Droids (2-player: the one opponent).
    foreach (OpponentsOf(intval($player)) as $opp) {
        SWUCreateUnitTokens($opp, 'TWI_T01', $x);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_125:0"] = function($player, $mzID = '') {
// The Clone Wars — "Pay any number of resources. Create that many Clone Trooper
                          // tokens. Each opponent creates that many Battle Droid tokens."
            $maxX = SWUResourceCount(intval($player), readyOnly: true);
            if ($maxX <= 0) return; // no resources to pay → 0 tokens
            DecisionQueueController::AddDecision(intval($player), 'NUMBERCHOOSE', "0|{$maxX}", 1,
                tooltip: 'Pay_any_number_of_resources_(that_many_Clone_Troopers;_opponent_gets_Battle_Droids)');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_125#0', 1);
            return;
};
