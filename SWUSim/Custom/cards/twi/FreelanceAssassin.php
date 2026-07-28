<?php
// TWI_212
// Cost 3 - Freelance Assassin - [Cunning] - Power 4 - HP 2
// Text: When Played: You may pay 2 resources. If you do, deal 2 damage to a unit.

// TWI_212 Freelance Assassin — "When Played: You may pay 2 resources. If you do, deal 2 damage to a unit."
$whenPlayedAbilities["TWI_212:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // Offer only when the player can pay 2 AND there is a unit to damage.
    if (SWUResourceCount(intval($player), true) < 2) return; // ready resources
    $anyUnit = false;
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        if (!empty(ZoneSearch($z, AnyUnitFilter))) { $anyUnit = true; break; }
    }
    if (!$anyUnit) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_2_resources_to_deal_2_damage_to_a_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_212#0", 1);
};

$customDQHandlers["TWI_212#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    SWUExhaustResources(intval($player), 2); // pay 2
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_unit", "DEAL_UNIT_DAMAGE|2");
};
