<?php
// TS26_71
// Cost 3 - Take Action - [Aggression]
// Text: Deal 3 damage to a unit. (Cost reduction via $playCostModifiers["TS26_71"].)

$whenPlayedAbilities["TS26_71:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3,
        'prompt' => "Deal_3_damage_to_a_unit",
    ]);
};
