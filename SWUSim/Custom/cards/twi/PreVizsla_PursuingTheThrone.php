<?php
// TWI_010
// Cost 5 - Pre Vizsla - Pursuing the Throne - [Aggression,Villainy] - Power 4 - HP 6
// Text: Action [1 resource, Exhaust]: Deal damage to a unit equal to the number of cards you've drawn this phase. (This doesn't include cards drawn in the regroup phase.)
// DeployText: While you have 3 or more cards in your hand, this unit gains Saboteur. / While you have 6 or more cards in your hand, this unit gets +2/+0.
// Epic Action: If you control 5 or more resources, deploy this leader.

// TWI_010 Pre Vizsla (front) — "Action [1 resource, Exhaust]: Deal damage to a unit equal to the number
// of cards you've drawn this phase."
$leaderActionResourceCosts["TWI_010"] = 1;

$leaderAbilities["TWI_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 1))) { SWUAfterAction($player); return; }
    $n = GlobalEffectCount($player, 'SWU_DREW_PHASE');
    $targets = SWUAllUnits();
    if ($n <= 0 || empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_{$n}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$n}");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
