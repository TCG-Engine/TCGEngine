<?php
// LOF_094
// Cost 2 - Jedi Consular - [Command,Heroism] - Power 1 - HP 4
// Text: Action [Exhaust, use the Force (lose your Force token)]: Play a unit from your hand. It costs 2 resources less.

// LOF_094 Jedi Consular — Action [Exhaust, use the Force]: play a unit from hand at −2. The exhaust is
// paid by the framework; the Force gate is in SWUUnitActionAffordable; consume the Force here.
$unitAbilities["LOF_094"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    UseTheForce(intval($player));   // Force pre-pay, then the standard discount-play offer
    SWUOfferDiscountPlay($player,
        ['discount' => 2, 'types' => ['Unit'], 'may' => true, 'prompt' => "Play_a_unit_from_your_hand_(it_costs_2_less)"]);
};
