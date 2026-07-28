<?php
// SHD_011
// Cost 4 - Kylo Ren - Rash and Deadly - [Villainy,Aggression] - Power 5 - HP 4
// Text: Action [Exhaust, discard a card from your hand]: Give a unit +2/+0 for this phase.
// DeployText: This unit gets -1/-0 for each card in your hand.
// Epic Action: If you control 4 or more resources, deploy this leader.

// ── SHD_011 Kylo Ren ───────────────────────────────────────────────────────────
// Front Action [Exhaust, discard a card from your hand]: Give a unit +2/+0 for this phase.
// Deployed passive: "This unit gets -1/-0 for each card in your hand" (in ObjectCurrentPower).
$leaderAbilities["SHD_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }   // can't pay the discard cost
    SWUQueueChooseTarget($player, $hand, "Discard_a_card_from_your_hand_(cost)", "SHD_011#cost");
};

$customDQHandlers["SHD_011#cost"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ), fn($mz) => ($o = GetZoneObject($mz)) !== null && empty($o->removed)));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+2/+0_this_phase", "APPLY_PHASE_BUFF|2|0|SHD_011");
    SWUQueueAfterAction(intval($player));
};
