<?php
// IBH_001
// Cost 5 - Leia Organa - Get to Your Transports! - [Command,Heroism] - Power 3 - HP 7
// Text: Action [1 resource, Exhaust]: Heal 1 damage from a friendly unit.
// DeployText: On Attack: Heal 1 damage from a friendly unit and 1 damage from another friendly unit.
// Epic Action: If you control 5 or more resources, deploy this leader. (Flip her, ready her, and move her to the ground arena.)

// IBH_001 Leia Organa (deployed) — On Attack: heal 1 from a friendly unit and 1 from another friendly
// unit. MZMAYCHOOSE first (OnAttack-safe), then the second pick from a continuation (excludes the first).
$onAttackAbilities["IBH_001:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'IBH_001#0','side'=>'my','may'=>true,
        'question'=>"Heal_1_from_a_friendly_unit?",'prompt'=>"Choose_a_friendly_unit"]);
};

// IBH_001 Leia Organa — Leader Action [1 resource, Exhaust]: heal 1 damage from a friendly unit.
$leaderAbilities["IBH_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = SWUAllUnits('my');
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Heal_1_from_a_friendly_unit", "HEAL_TARGET|1");
    SWUQueueAfterAction($player);
};

$customDQHandlers["IBH_001#0"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  $first = GetZoneObject($lastDecision);
  $firstUID = SWUObjUID($first, 0);
  OnHealUnit(intval($player), $lastDecision, 1);
  SWUOfferUnitTarget($player, '', ['continuation'=>'HEAL_TARGET','amount'=>1,'side'=>'my','excludeUID'=>$firstUID,
      'prompt'=>"Heal_1_from_another_friendly_unit"]);
};
