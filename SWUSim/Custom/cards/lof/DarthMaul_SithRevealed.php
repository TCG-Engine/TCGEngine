<?php
// LOF_009
// Cost 6 - Darth Maul - Sith Revealed - [Aggression,Villainy] - Power 5 - HP 6
// Text: Action [Exhaust, use the Force (lose your Force token)]: Deal 1 damage to a unit and 1 damage to a different unit.
// DeployText: On Attack: Deal 1 damage to a unit and 1 damage to a different unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

// LOF_009 Darth Maul — On Attack: Deal 1 damage to a unit and 1 damage to a DIFFERENT unit. MANDATORY:
// you must damage min(2, units in play) units — including a friendly unit or Maul himself if there is no
// other target (Intended: he must damage himself if there are no other units in play). Both picks are mandatory
// choose-targets (a single valid target auto-resolves via PASSPARAMETER).
$onAttackAbilities["LOF_009:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_unit", "LOF_009#2");
};

// LOF_009 Darth Maul — Action [Exhaust, use the Force]: Deal 1 damage to a unit and 1 damage to a
// different unit.
$leaderAbilities["LOF_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_damage_to_a_unit", "LOF_009#0");
};

$customDQHandlers["LOF_009#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $firstUID = intval(GetZoneObject($lastDecision)->UniqueID ?? -1);
    // Second target: a DIFFERENT unit.
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                         ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $firstUID) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_different_unit", "LOF_009#1");
};

$customDQHandlers["LOF_009#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUAfterAction(intval($player));
};

$customDQHandlers["LOF_009#2"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  SWUDealDamageToUnit($lastDecision, 1, intval($player));
  $firstUID = intval(GetZoneObject($lastDecision)->UniqueID ?? -1);
  $targets = [];
  foreach (SWUAllUnits() as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $firstUID)
      continue;
    $targets[] = $mz;
  }
  if (empty($targets))
    return;
  DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", implode('&', array_values($targets)), 1, tooltip: "Deal_1_damage_to_a_different_unit");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEAL_UNIT_DAMAGE|1", 1);
};
