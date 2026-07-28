<?php
// TWI_064
// Cost 5 - Ki-Adi-Mundi - Composed and Confident - [Vigilance] - Power 5 - HP 7
// Text: Coordinate - When an opponent plays their second card each phase: You may draw 2 cards.

// TWI_064 Ki-Adi-Mundi continuation — draw 2 on YES (the "you may" draw).
$customDQHandlers["TWI_064#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === 'YES') DoDrawCard(intval($player), 2);
};
