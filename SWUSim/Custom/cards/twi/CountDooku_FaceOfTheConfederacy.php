<?php
// TWI_005
// Cost 7 - Count Dooku - Face of the Confederacy - [Command,Villainy] - Power 5 - HP 9
// Text: Action [Exhaust]: Play a Separatist card from your hand. It gains Exploit 1. (You may defeat 1 unit you control. If you do, that card costs 2 resources less.)
// DeployText: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / On Attack: The next Separatist card you play this phase gains Exploit 3.
// Epic Action: If you control 7 or more resources, deploy this leader.

// TWI_005 Count Dooku (deployed Leader Unit) — On Attack: the next Separatist card
// you play this phase gains Exploit 3 (additive with any printed Exploit).
// Arms a one-shot lingering flag consumed in SWUBeginPlayCard; cleared at RegroupPhaseStart.
$onAttackAbilities["TWI_005:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_DOOKU_NEXT_SEPARATIST_EXPLOIT');
};

// Resolves Count Dooku LEADER side: "Play a Separatist card from your hand. It gains Exploit 1."
// Sets $gPlayGrantedExploit = 1 and delegates to SWUBeginPlayCard (which immediately resets
// the global after capturing it, preventing any grant leak). ActivateCard / SWUBeginPlayCard
// own the end-of-action — do NOT append SWU_AFTER_ACTION here.
$customDQHandlers["TWI_005#0"] = function($player, $parts, $lastDecision) {
    global $gPlayGrantedExploit, $playerID;
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction($player);
        return;
    }
    $playerID = intval($player);
    $gPlayGrantedExploit = 1; // grant Exploit 1 to the chosen Separatist card
    SWUBeginPlayCard(intval($player), $lastDecision);
};

// TWI_005 Count Dooku — Leader Action [Exhaust]: Play a Separatist card from your hand.
// It gains Exploit 1. Affordability (≥1 affordable Separatist in hand) is checked in
// SWULeaderActionAffordable. The deployed-unit side uses the same TWI_005 handler
// via $unitAbilities["TWI_005"] in CardDQHandlers.php.
$leaderAbilities["TWI_005"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = _SWUSeparatistHandPlayables($player);
    if (empty($targets)) { SWUAfterAction($player); return; } // safety net — gated upstream
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_Separatist_card_to_play_(it_gains_Exploit_1)');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'TWI_005#0', 1);
};
