<?php
// LOF_015
// Cost 4 - Cal Kestis - I Can't Keep Hiding - [Cunning,Heroism] - Power 3 - HP 4
// Text: Action [Exhaust, use the Force (lose your Force token)]: An opponent chooses a ready unit they control. Exhaust that unit.
// DeployText: On Attack: An opponent chooses a ready unit they control. Exhaust that unit.
// Epic Action: If you control 4 or more resources, deploy this leader.

// LOF_015 Cal Kestis — On Attack: an opponent chooses a ready unit they control; exhaust it. Cross-player,
// queued via an intermediate CUSTOM (survives the OnAttack $playerID restore); combat owns the after-action.
$onAttackAbilities["LOF_015:0"] = function($player, $mzID) {
    $elig015 = _SWUCal015Eligible(intval($player));
    if (empty($elig015)) return;                                        // nobody can act → nothing happens
    SWUQueueChooseOpponent(intval($player), "LOF_015#3|" . intval($player),
        "Choose_an_opponent_to_exhaust_a_ready_unit", $elig015);
};

// LOF_015 Cal Kestis — Action [Exhaust, use the Force]: An opponent chooses a ready unit they control.
// Exhaust that unit. The opponent's choice is queued via an intermediate CUSTOM (LOF_015_OPP) so it
// survives SWULeaderAction's $playerID restore; the chain owns the leader after-action in every branch.
$leaderAbilities["LOF_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $elig015 = _SWUCal015Eligible(intval($player));
    if (empty($elig015)) { SWUAfterAction(intval($player)); return; }   // nobody can act → gate OUTSIDE the picker
    SWUQueueChooseOpponent(intval($player), "LOF_015#2|" . intval($player),
        "Choose_an_opponent_to_exhaust_a_ready_unit", $elig015);
};

// Shared opponent picker for BOTH of Cal's sides — "AN opponent chooses a ready unit they control".
// OFFICIAL RULING (07/14/2025): "If there are multiple opponents, the controlling player chooses which
// one will be 'an opponent.'"
// ⚠ FILTER to opponents controlling at least one READY unit (taxonomy shape 1 — the chosen player acts on
// their OWN board): with no ready unit they cannot choose, cannot exhaust anything, and the pick is a
// choice among nothing. Ready-only, not "has any unit" — an all-exhausted board is just as unable to act.
// ⚠ THE GATE AND SWUAfterAction MUST STAY OUTSIDE THE PICKER. SWUQueueChooseOpponent queues NOTHING at
// zero eligible, so an after-action placed in the continuation would never fire and the Action would hang
// — this is the LeaderAbility_NoReadyUnits_StillUsable path.
// $parts[0] = caster, $parts[1] = the continuation to run with the picked seat (#2 front / #3 deployed).
function _SWUCal015Eligible(int $caster): array {
    global $playerID;
    $out = [];
    foreach (OpponentsOf($caster) as $o) {
        $sp = $playerID; $playerID = $o;
        foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
            $u = GetZoneObject($mz);
            if (!SWUObjGone($u) && intval($u->Status ?? 0) === 1) { $out[] = $o; break; }
        }
        $playerID = $sp;
    }
    return array_values(array_unique($out));
}

$customDQHandlers["LOF_015#3"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $caster = intval($parts[0] ?? $player);
  $opp = SWUPickedOpponent($lastDecision);
  if ($opp <= 0 || $opp === $caster) return;
  $playerID = $opp;
  $units = [];
  foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o))
      continue;
    if (intval($o->Status ?? 0) === 1)
      $units[] = $mz;
  }
  if (empty($units)) {
    $playerID = $caster;
    return;
  }
  if (count($units) === 1) {
    $o = GetZoneObject($units[0]);
    if ($o !== null && empty($o->removed))
      $o->Status = 0;
    $playerID = $caster;
    return;
  }
  DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $units), 1, tooltip: "Choose_a_ready_unit_to_exhaust");
  DecisionQueueController::AddDecision($opp, "CUSTOM", "LOF_015#1", 1);
};

$customDQHandlers["LOF_015#1"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $o = GetZoneObject($lastDecision);
  if ($o !== null && empty($o->removed))
    $o->Status = 0;
};

$customDQHandlers["LOF_015#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) { SWUAfterAction($caster); return; }
    $playerID = $opp;
    $units = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Status ?? 0) === 1) $units[] = $mz; // ready units only
    }
    if (empty($units)) { $playerID = $caster; SWUAfterAction($caster); return; }
    if (count($units) === 1) {
        $o = GetZoneObject($units[0]);
        if ($o !== null && empty($o->removed)) $o->Status = 0; // exhaust
        $playerID = $caster; SWUAfterAction($caster); return;
    }
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $units), 1, tooltip: "Choose_a_ready_unit_to_exhaust");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LOF_015#0|{$caster}", 1);
    // leave $playerID = $opp so MZCountChoices resolves the relative mzIDs under the opponent
};

$customDQHandlers["LOF_015#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $playerID = intval($player); // opponent frame to resolve their relative mzID
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) $o->Status = 0; // exhaust
    }
    $playerID = $caster;
    SWUAfterAction($caster);
};
