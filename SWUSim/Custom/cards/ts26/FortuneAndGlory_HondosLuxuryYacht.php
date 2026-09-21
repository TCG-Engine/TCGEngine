<?php
// TS26_27
// Cost 4 - Fortune and Glory - Hondo's Luxury Yacht - [Command,Cunning] - Power 3 - HP 5
// Text: When Played: This unit captures a non-leader unit. / Bounty - A friendly unit captures a non-leader unit. (When this unit is defeated or captured, your opponent collects its bounty.)

// TS26_27 Fortune and Glory — When Played: this unit captures a non-leader unit. (Bounty payoff is in
// the SWUCollectBounty switch.)
$whenPlayedAbilities["TS26_27:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $captorUID = SWUObjUID($self);
    $tg = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -2) !== $captorUID) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Capture_a_non-leader_unit", "TS26_27#0|" . $captorUID, may: true);
};

// Shared capture continuation: capture $lastDecision with the captor whose UID is in parts[0].
$customDQHandlers["TS26_27#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $captorMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captorMz === null) return;
    DoCaptureUnit(intval($player), $captorMz, $lastDecision);
};

// TS26_27 bounty continuation — the collector chose a captor; now choose a non-leader unit to capture.
$customDQHandlers["TS26_27#1"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (!$lastDecision || !str_contains($lastDecision, '-'))
    return;
  $captor = GetZoneObject($lastDecision);
  $captorUID = SWUObjUID($captor);
  $tg = [];
  foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -2) !== $captorUID)
        $tg[] = $mz;
    }
  }
  if (empty($tg))
    return;
  SWUQueueChooseTarget(intval($player), $tg, "Capture_a_non-leader_unit", "TS26_27#0|" . $captorUID, may: true);
};
