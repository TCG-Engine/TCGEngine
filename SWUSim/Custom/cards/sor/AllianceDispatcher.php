<?php
// SOR_093
// Cost 1 - Alliance Dispatcher - [Command,Heroism] - Power 1 - HP 2
// Text: Action [exhaust]: Play a unit from your hand. It costs [1 resource] less.

// Shared discount-play family — see SWUOfferDiscountPlay in CardHelpers.php.
$unitAbilities["SOR_093"] = fn($player, $mzID) => SWUOfferDiscountPlay($player,
    ['discount' => 1, 'types' => ['Unit'], 'may' => true, 'prompt' => "Play_a_unit_from_your_hand_(it_costs_1_less)"]);
