<?php
// TWI_036
// Cost 5 - Devastating Gunship - [Vigilance,Villainy] - Power 3 - HP 5
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played: Defeat an enemy unit with 2 or less remaining HP.

// TWI_036 Devastating Gunship — "When Played: Defeat an enemy unit with 2 or less remaining HP." (Grit is
// a keyword.)
$whenPlayedAbilities["TWI_036:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'side' => 'their',
        'extraFilter' => fn($o) => (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) <= 2,
        'prompt' => "Defeat_an_enemy_unit_with_2_or_less_remaining_HP",
    ]);
};
