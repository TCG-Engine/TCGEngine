<?php
// SEC_142
// Cost 8 - Fulminatrix - Fleet Killer - [Aggression,Villainy] - Power 9 - HP 7
// Text: When Played/On Attack: You may deal 4 damage to a ground unit.

// SEC_142 Fulminatrix — When Played / On Attack: you may deal 4 to a ground unit.
$sec142 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $targets = SWUAllUnits(null, GroundArena);
  if (empty($targets))
    return;
  SWUQueueMayChooseTarget(intval($player), $targets, "Deal_4_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|4");
};

$whenPlayedAbilities["SEC_142:0"] = $sec142;

$onAttackAbilities["SEC_142:0"] = $sec142;
