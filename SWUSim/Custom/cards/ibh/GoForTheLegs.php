<?php
// IBH_018 / IBH_045
// Cost 1 - Go for the Legs - [Cunning]
// Text: Exhaust an enemy ground unit.

$whenPlayedAbilities["IBH_018:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Exhaust_an_enemy_ground_unit",
    ]);
};
$whenPlayedAbilities["IBH_045:0"] = $whenPlayedAbilities["IBH_018:0"];
