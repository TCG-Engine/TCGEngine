<?php
// SEC_142
// Cost 8 - Fulminatrix - Fleet Killer - [Aggression,Villainy] - Power 9 - HP 7
// Text: When Played/On Attack: You may deal 4 damage to a ground unit.

// SEC_142 Fulminatrix — When Played / On Attack: you may deal 4 to a ground unit.
$sec142 = function ($player, $mzID) {
  SWUOfferUnitTarget($player, $mzID, [
    'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 4, 'arena' => 'Ground', 'may' => true,
    'question' => "Deal_4_to_a_ground_unit?", 'prompt' => "Choose_a_ground_unit",
  ]);
};

$whenPlayedAbilities["SEC_142:0"] = $sec142;

$onAttackAbilities["SEC_142:0"] = $sec142;
