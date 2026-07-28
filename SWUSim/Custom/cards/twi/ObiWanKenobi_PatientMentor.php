<?php
// TWI_003
// Cost 6 - Obi-Wan Kenobi - Patient Mentor - [Vigilance,Heroism] - Power 4 - HP 7
// Text: Action [Exhaust]: Heal 1 damage from a unit.
// DeployText: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / On Attack: Heal 1 damage from a unit. If you do, deal 1 damage to a different unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

// TWI_003 Obi-Wan Kenobi (deployed) — Sentinel + "On Attack: Heal 1 damage from a unit. If you do, deal
// 1 damage to a different unit."
$onAttackAbilities["TWI_003:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'TWI_003#0','may'=>true,
        'extraFilter'=>fn($o)=>intval($o->Damage ?? 0) > 0,
        'question'=>"Heal_1_from_a_unit_(then_deal_1_to_a_different_unit)?",'prompt'=>"Choose_a_unit_to_heal"]);
};

// TWI_003 Obi-Wan Kenobi (front) — "Action [Exhaust]: Heal 1 damage from a unit."
$leaderAbilities["TWI_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = SWUAllUnits();
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Heal_1_damage_from_a_unit", "HEAL_TARGET|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

$customDQHandlers["TWI_003#0"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $healed = GetZoneObject($lastDecision);
  if (SWUObjGone($healed))
    return;
  $healedUID = intval($healed->UniqueID ?? 0);
  OnHealUnit(intval($player), $lastDecision, 1); // heal 1 from the chosen unit
  // then deal 1 to a DIFFERENT unit
  SWUOfferUnitTarget($player, '', [
    'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'excludeUID' => $healedUID,
    'prompt' => "Deal_1_damage_to_a_different_unit",
  ]);
};
