<?php
// LOF_071
// Cost 7 - Grappling Guardian - [Vigilance] - Power 3 - HP 9
// Text: When Played: You may defeat a space unit with 6 or less remaining HP.

// LOF_071 Grappling Guardian — When Played: may defeat a space unit with 6 or less remaining HP.
$whenPlayedAbilities["LOF_071:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'arena' => 'Space', 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 6,
        'question' => "Defeat_a_space_unit_with_6_or_less_HP?", 'prompt' => "Choose_a_space_unit",
    ]);
};
