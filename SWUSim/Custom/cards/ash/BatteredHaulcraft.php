<?php
// ASH_071
// Cost 2 - Battered Haulcraft - [Vigilance] - Power 2 - HP 3
// Text: When Played: Deal 1 damage to this unit and 1 damage to an enemy space unit.

// ASH_071 Battered Haulcraft — When Played: deal 1 damage to this unit and 1 damage to an enemy space
// unit. (Self-damage is mandatory; the enemy-space hit fizzles cleanly if there's no enemy space unit.)
$whenPlayedAbilities["ASH_071:0"] = function($player, $mzID) {
    SWUDealDamageToUnit($mzID, 1, intval($player));   // 1 to this unit (mandatory)
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'their', 'arena' => 'Space',
        'prompt' => "Deal_1_to_an_enemy_space_unit",
    ]);
};
