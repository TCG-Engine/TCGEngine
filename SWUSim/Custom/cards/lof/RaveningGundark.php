<?php
// LOF_259
// Cost 5 - Ravening Gundark - Power 5 - HP 4
// Text: When Played: Deal 1 damage to a ground unit.

// LOF_259 Ravening Gundark — When Played: deal 1 damage to a ground unit.
$whenPlayedAbilities["LOF_259:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'arena' => 'Ground',
        'prompt' => "Deal_1_to_a_ground_unit",
    ]);
};
