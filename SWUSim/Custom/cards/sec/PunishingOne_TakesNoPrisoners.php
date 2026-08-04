<?php
// SEC_171
// Cost 5 - Punishing One - Takes No Prisoners - [Aggression] - Power 3 - HP 5
// Text: This unit gains Raid 1 for each damaged enemy unit. / When Played/On Attack: You may deal 1 damage to a unit.

// SEC_171 Punishing One — (Raid passive in KeywordEffects) + When Played / On Attack: may deal 1 to a unit.
$sec171 = function ($player, $mzID) {
  SWUOfferUnitTarget($player, $mzID, [
    'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'may' => true,
    'question' => "Deal_1_to_a_unit?", 'prompt' => "Deal_1_damage_to_a_unit",
  ]);
};

$whenPlayedAbilities["SEC_171:0"] = $sec171;

$onAttackAbilities["SEC_171:0"] = $sec171;
