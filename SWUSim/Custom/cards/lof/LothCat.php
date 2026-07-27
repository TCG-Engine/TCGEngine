<?php
// LOF_207
// Cost 2 - Loth-Cat - [Cunning] - Power 2 - HP 1
// Text: When Played/When Defeated: You may exhaust a ground unit.

// LOF_207 Loth-Cat — When Played/When Defeated: may exhaust a ground unit.
$whenPlayedAbilities["LOF_207:0"] =
$whenDefeatedAbilities["LOF_207:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'arena' => 'Ground', 'may' => true,
        'question' => "Exhaust_a_ground_unit?", 'prompt' => "Choose_a_ground_unit",
    ]);
};
