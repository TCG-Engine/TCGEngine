<?php
// TWI_015
// Cost 6 - General Grievous - General of the Droid Armies - [Cunning,Villainy] - Power 4 - HP 8
// Text: Action [Exhaust]: Give a Droid unit Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.)
// DeployText: On Attack: You may give a Droid unit +1/+0 and Sentinel for this phase.
// Epic Action: If you control 6 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)

// TWI_015 General Grievous (front continuation) — give the chosen Droid unit Sentinel this phase.
$customDQHandlers["TWI_015#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'SENTINEL');
    SWUAfterAction(intval($player));
};

// TWI_015 General Grievous (deployed) — "On Attack: You may give a Droid unit +1/+0 and Sentinel for this
// phase."
$onAttackAbilities["TWI_015:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $droids = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Droid')) $droids[] = $mz;
        }
    }
    if (empty($droids)) return;
    SWUQueueMayChooseTarget(intval($player), $droids, "Give_a_Droid_unit_+1/+0_and_Sentinel_this_phase?", "Choose_a_Droid_unit", "TWI_015#1");
};

// TWI_015 General Grievous (front) — "Action [Exhaust]: Give a Droid unit Sentinel for this phase."
$leaderAbilities["TWI_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $droids = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Droid')) $droids[] = $mz;
        }
    }
    if (empty($droids)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $droids, "Give_a_Droid_unit_Sentinel_this_phase", "TWI_015#0");
};

$customDQHandlers["TWI_015#1"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $o = GetZoneObject($lastDecision);
  if (SWUObjGone($o))
    return;
  SWUApplyPhaseBuff($lastDecision, 1, 0, 'TWI_015');
  AddTurnEffect($lastDecision, 'SENTINEL');
};
