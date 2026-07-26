<?php
// LAW_185
// Cost 9 - Ben Solo - Facing the Light - [Aggression,Heroism] - Power 8 - HP 8
// Text: Hidden / When Played/When Defeated: Ready another friendly unit. It can't be attacked this phase.

$customDQHandlers["LAW_185#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnReadyCard(intval($player), $lastDecision);
    AddTurnEffect($lastDecision, 'CANT_BE_ATTACKED');
};

// LAW_185 Ben Solo — Hidden + When Played/When Defeated: ready another friendly unit; it can't be
// attacked this phase.
$law185 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $self = ($mzID !== '' && str_contains((string) $mzID, '-')) ? GetZoneObject($mzID) : null;
  $uid = SWUObjUID($self, 0);
  $targets = [];
  foreach (SWUAllUnits('my') as $mz) {
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid)
      $targets[] = $mz;
  }
  if (empty($targets))
    return;
  SWUQueueChooseTarget(intval($player), $targets, "Ready_another_friendly_unit_(it_can't_be_attacked_this_phase)", "LAW_185#0");
};

$whenPlayedAbilities["LAW_185:0"] = $law185;

$whenDefeatedAbilities["LAW_185:0"] = $law185;
