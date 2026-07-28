<?php
// SEC_202
// Cost 3 - Rebel Propagandist - [Cunning,Heroism] - Power 2 - HP 4
// Text: When Played/When Defeated: Give another friendly unit +1/+0 and Saboteur for this phase. (When that unit attacks, ignore Sentinel and defeat the defender's Shields.)

$customDQHandlers["SEC_202#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUApplyPhaseBuff($lastDecision, 1, 0, 'SEC_202');
    AddTurnEffect($lastDecision, 'SABOTEUR^SEC_202');   // Saboteur this phase (source = SEC_202)
};

// SEC_202 Rebel Propagandist — When Played / When Defeated: give ANOTHER friendly unit +1/+0 and
// Saboteur for this phase.
$sec202 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $self = GetZoneObject($mzID);
  // The positional mzID can be STALE by When-Defeated dispatch time: the defeated Rebel Propagandist
  // has been cleaned up and a surviving friendly unit shifted into its slot, so GetZoneObject($mzID)
  // now returns that ally. Only treat the slot as "self" when it is actually a live SEC_202 (the When
  // Played case); on defeat, self has left play, so every surviving friendly counts as "another".
  $selfUID = ($self && ($self->CardID ?? '') === 'SEC_202' && empty($self->removed))
    ? intval($self->UniqueID ?? 0) : 0;
  $friendly = [];
  foreach (SWUAllUnits('my') as $mz) {
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID)
      $friendly[] = $mz;
  }
  if (empty($friendly))
    return;
  SWUQueueChooseTarget(intval($player), $friendly, "Give_another_friendly_unit_+1/+0_and_Saboteur", "SEC_202#0");
};

$whenPlayedAbilities["SEC_202:0"] = $sec202;

$whenDefeatedAbilities["SEC_202:0"] = $sec202;
