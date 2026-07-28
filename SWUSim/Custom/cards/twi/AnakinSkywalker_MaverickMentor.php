<?php
// TWI_147
// Cost 5 - Anakin Skywalker - Maverick Mentor - [Aggression,Heroism] - Power 6 - HP 6
// Text: Coordinate - On Attack: Draw a card. (Gain this ability while you control 3 or more units.)

// TWI_147 Anakin Skywalker — "Coordinate - On Attack: Draw a card."
$onAttackAbilities["TWI_147:0"] = function($player, $mzID) {
    if (IsCoordinateActive(intval($player))) DoDrawCard(intval($player), 1);
    // Combat owns the after-action.
};
