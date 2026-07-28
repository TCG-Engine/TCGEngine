<?php
// TWI_120
// Cost 1 - Strategic Acumen - [Command] - Upgrade Power 0 - Upgrade HP 2
// Text: Attached unit gains: "Action [Exhaust]: Play a unit from your hand. It costs 1 resource less."

// Shared discount-play family — see SWUOfferDiscountPlay in CardHelpers.php.
$unitAbilities["TWI_120"] = fn($player, $mzID) => SWUOfferDiscountPlay($player,
    ['discount' => 1, 'types' => ['Unit'], 'prompt' => "Play_a_unit_from_your_hand_(it_costs_1_less)"]);
