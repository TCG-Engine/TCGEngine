<?php
// SOR_134
// Cost 6 - Ruthless Raider - [Aggression,Villainy] - Power 4 - HP 6
// Text: When Played/When Defeated: Deal 2 damage to an enemy base and 2 damage to an enemy unit.

// SOR_134 Ruthless Raider — When Played / When Defeated: deal 2 to an enemy base AND 2 to an enemy unit.
$sor134RuthlessRaider = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  SWUDealDamageToBase(2, GetOpponent(intval($player)));
  SWUOfferUnitTarget($player, $mzID, [
    'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their',
    'prompt' => "Deal_2_to_an_enemy_unit",
  ]);
};

$whenPlayedAbilities["SOR_134:0"] = $sor134RuthlessRaider;

$whenDefeatedAbilities["SOR_134:0"] = $sor134RuthlessRaider;
