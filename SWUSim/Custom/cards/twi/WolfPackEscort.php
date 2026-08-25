<?php
// TWI_191
// Cost 1 - Wolf Pack Escort - [Cunning,Heroism] - Power 2 - HP 1
// Text: When Played: You may return a friendly non-leader, non-Vehicle unit to its owner's hand.

// TWI_191 Wolf Pack Escort — "When Played: You may return a friendly non-leader, non-Vehicle unit to its
// owner's hand."
$whenPlayedAbilities["TWI_191:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'side' => 'friendly', 'nonLeader' => true, 'notTraits' => ['Vehicle'], 'may' => true,
        'question' => "You_may_return_a_friendly_non-Vehicle_unit_to_hand", 'prompt' => "Return_a_friendly_non-Vehicle_unit",
    ]);
};
