<?php
// LOF_177
// Cost 4 - Time of Crisis - [Aggression]
// Text: Each player chooses a unit they control. Deal 3 damage to each unit not chosen this way.

// LOF_177 Echoes of the Force — both players spare a unit they control, then 3 damage hits every other
// unit. The opponent's pick is queued via an intermediate CUSTOM (survives the $playerID restore).
$customDQHandlers["LOF_177#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $caster = intval($parts[0] ?? $player);
  $myUID = -1;
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
    $o = GetZoneObject($lastDecision);
    if ($o !== null)
      $myUID = intval($o->UniqueID ?? -1);
  }
  DecisionQueueController::AddDecision($caster, "CUSTOM", "LOF_177#1|{$caster}|{$myUID}", 1);
};

$customDQHandlers["LOF_177#1"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $caster = intval($parts[0] ?? $player);
  $myUID = intval($parts[1] ?? -1);
  $opp = OtherPlayer($caster);
  $playerID = $opp;
  $theirs = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
  if (empty($theirs)) {
    TimeofCrisisResolve($caster, $myUID, -1);
    return;
  }
  if (count($theirs) === 1) {
    $o = GetZoneObject($theirs[0]);
    TimeofCrisisResolve($caster, $myUID, ($o !== null) ? intval($o->UniqueID ?? -1) : -1);
    return;
  }
  DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $theirs), 1, tooltip: "Choose_a_unit_you_control_(spared_from_the_3_damage)");
  DecisionQueueController::AddDecision($opp, "CUSTOM", "LOF_177#2|{$caster}|{$myUID}", 1);
};

$customDQHandlers["LOF_177#2"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $caster = intval($parts[0] ?? $player);
  $myUID = intval($parts[1] ?? -1);
  $oppUID = -1;
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
    $playerID = intval($player); // opponent frame to resolve their relative mzID
    $o = GetZoneObject($lastDecision);
    if ($o !== null)
      $oppUID = intval($o->UniqueID ?? -1);
  }
  TimeofCrisisResolve($caster, $myUID, $oppUID);
};

function TimeofCrisisResolve(int $caster, int $myUID, int $oppUID): void
{
  global $playerID;
  $playerID = $caster;
  $uids = [];
  foreach ([1, 2] as $pl) {
    foreach (GetUnitsInPlay($pl) as $u) {
      if (empty($u->removed)) {
        $uid = intval($u->UniqueID ?? -1);
        if ($uid !== $myUID && $uid !== $oppUID)
          $uids[] = $uid;
      }
    }
  }
  foreach ($uids as $uid) {
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null && $mz !== '')
      SWUDealDamageToUnit($mz, 3, $caster);
  }
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_177:0"] = function($player, $mzID = '') {
// Echoes of the Force — "Each player chooses a unit they control. Deal 3 damage to
                          // each unit not chosen this way." Caster picks first, then the opponent.
            global $playerID; $playerID = intval($player);
            $mine = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (empty($mine)) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_177#1|" . intval($player) . "|-1", 1);
                return;
            }
            SWUQueueChooseTarget(intval($player), $mine, "Choose_a_unit_you_control_(spared_from_the_3_damage)", "LOF_177#0|" . intval($player));
            return;
};
