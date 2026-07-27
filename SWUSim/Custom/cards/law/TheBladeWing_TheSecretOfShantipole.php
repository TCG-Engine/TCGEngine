<?php
// LAW_241
// Cost 6 - The Blade Wing - The Secret of Shantipole - [Cunning] - Power 3 - HP 3
// Text: When Played: You may return a non-leader unit to its owner's hand.

// LAW_241 The Blade Wing — When Played: you may return a non-leader unit to its owner's hand.
$whenPlayedAbilities["LAW_241:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true, 'may' => true,
        'question' => "Return_a_non-leader_unit_to_hand?", 'prompt' => "Choose_a_unit",
    ]);
};
