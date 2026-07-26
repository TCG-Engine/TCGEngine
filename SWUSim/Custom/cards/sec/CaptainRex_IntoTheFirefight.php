<?php
// SEC_048
// Cost 6 - Captain Rex - Into the Firefight - [Vigilance,Heroism] - Power 7 - HP 7
// Text: When Played/When this unit completes an attack: Give this unit and an enemy unit Sentinel for this phase.

// SEC_048 Captain Rex — When Played / When this unit completes an attack: give this unit AND an enemy
// unit Sentinel for this phase.
$sec048 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $self = GetZoneObject($mzID);
  if ($self !== null && empty($self->removed))
    AddTurnEffect($mzID, 'SENTINEL^SEC_048');   // give itself Sentinel
  $enemy = SWUAllUnits('their');
  if (empty($enemy))
    return;
  SWUQueueChooseTarget(intval($player), $enemy, "Give_an_enemy_unit_Sentinel", "GRANT_PHASE_KEYWORD|SENTINEL^SEC_048");
};

$whenPlayedAbilities["SEC_048:0"] = $sec048;

$onAttackEndAbilities["SEC_048:0"] = $sec048;
