<?php
// SOR_134
// Cost 6 - Ruthless Raider - [Aggression,Villainy] - Power 4 - HP 6
// Text: When Played/When Defeated: Deal 2 damage to an enemy base and 2 damage to an enemy unit.

// SOR_134 Ruthless Raider — When Played / When Defeated: deal 2 to an enemy base AND 2 to an enemy unit.
$sor134RuthlessRaider = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  SWUDealDamageToBase(2, GetOpponent(intval($player)));
  $enemy = SWUAllUnits('their');
  if (empty($enemy))
    return;
  SWUQueueChooseTarget(intval($player), $enemy, "Deal_2_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|2");
};

$whenPlayedAbilities["SOR_134:0"] = $sor134RuthlessRaider;

$whenDefeatedAbilities["SOR_134:0"] = $sor134RuthlessRaider;
