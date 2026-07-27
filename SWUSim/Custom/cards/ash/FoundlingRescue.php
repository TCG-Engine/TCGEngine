<?php
// ASH_092
// Cost 4 - Foundling Rescue - [Vigilance]
// Text: You may defeat a unit with 2 or less remaining HP. Create a Mandalorian token.

$whenPlayedAbilities["ASH_092:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 2,
        'question' => "Defeat_a_unit_with_2_or_less_remaining_HP?", 'prompt' => "Choose_a_unit",
    ]);
    SWUCreateUnitToken(intval($player), 'ASH_T01');   // create the Mandalorian regardless
};
