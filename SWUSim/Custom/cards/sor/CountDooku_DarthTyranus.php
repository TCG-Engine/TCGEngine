<?php
// SOR_038
// Cost 7 - Count Dooku - Darth Tyranus - [Vigilance,Villainy] - Power 5 - HP 4
// Text: Shielded (When you play this unit, give him a Shield token.) / When Played: You may defeat a unit with 4 or less remaining HP.

// SOR_038 Count Dooku — When Played: you may defeat a unit with 4 or less remaining HP.
$whenPlayedAbilities["SOR_038:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 4,
        'question' => "Defeat_a_unit_with_4_or_less_remaining_HP?", 'prompt' => "Defeat_a_unit_with_4_or_less_remaining_HP",
    ]);
};
