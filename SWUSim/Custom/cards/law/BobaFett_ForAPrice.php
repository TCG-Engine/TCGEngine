<?php
// LAW_214
// Cost 5 - Boba Fett - For a Price - [Cunning,Villainy] - Power 6 - HP 5
// Text: When Played/On Attack: You may pay 1 resource. If you do, deal 3 damage to a ground unit.

$customDQHandlers["LAW_214#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUExhaustResources(intval($player), 1)) return;
    $ground = SWUAllUnits(null, GroundArena);
    if (empty($ground)) return;
    SWUQueueChooseTarget(intval($player), $ground, "Deal_3_to_a_ground_unit", "DEAL_UNIT_DAMAGE|3");
};

// LAW_214 Boba Fett — When Played/On Attack: you may pay 1 resource. If you do, deal 3 damage to a
// ground unit.
$law214 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  if (SWUResourceCount(intval($player), readyOnly: true) < 1)
    return;
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_deal_3_to_a_ground_unit?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_214#0", 1);
};

$whenPlayedAbilities["LAW_214:0"] = $law214;

$onAttackAbilities["LAW_214:0"] = $law214;
