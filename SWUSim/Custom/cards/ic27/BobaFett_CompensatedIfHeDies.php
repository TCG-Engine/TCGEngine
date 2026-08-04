<?php
// IC27_146
// Cost 5 - Boba Fett - Compensated If He Dies - [Cunning,Villainy] - Unit (Ground) 4/7 (unique)
//   Traits: Underworld, Bounty Hunter
// Text: When Attack Ends: If the defending unit was defeated, you may ready 2 resources.

// The "defending unit was defeated" gate is read from $combatCtx in SWUCollectCombatHitTriggers
// (CombatLogic), where the AddTrigger deliberately sits ABOVE the attacker-survival early-return —
// there is no "if this unit survived" clause, and the card's subtitle is precisely about him trading.
// Resources are fungible, so there is nothing to choose: SWUReadyResources readies up to 2 exhausted
// ones (fewer if fewer are exhausted).
function Ic27146ReadyResourcesTrigger($player): void {
    global $playerID; $playerID = intval($player);
    // Skip the prompt entirely when there is nothing to gain — a "may" with no possible effect must
    // not raise a decision (the SEC_186 pointless-prompt family).
    $exhausted = 0;
    foreach (GetResources(intval($player)) as $r) {
        if (empty($r->removed) && intval($r->Status ?? 1) === 0) $exhausted++;
    }
    if ($exhausted <= 0) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Ready_2_resources?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IC27_146#0", 1);
}

$customDQHandlers["IC27_146#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    SWUReadyResources(intval($player), 2);
};
