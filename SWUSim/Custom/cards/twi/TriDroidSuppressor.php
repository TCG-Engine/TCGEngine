<?php
// TWI_217
// Cost 7 - Tri-Droid Suppressor - [Cunning] - Power 4 - HP 7
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / When Played: Exhaust an enemy ground unit.

// TWI_217 Tri-Droid Suppressor — "Exploit 2. When Played: Exhaust an enemy ground unit."
$whenPlayedAbilities["TWI_217:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Exhaust_an_enemy_ground_unit",
    ]);
};
