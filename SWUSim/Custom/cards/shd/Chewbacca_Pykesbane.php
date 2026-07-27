<?php
// SHD_050
// Cost 8 - Chewbacca - Pykesbane - [Heroism,Vigilance] - Power 4 - HP 10
// Text: Grit / When Played: You may defeat a unit with 5 or less remaining HP. / Smuggle [9 resources Aggression Heroism]

// ─── SHD_050 Chewbacca ────────────────────────────────────────────────────────
// When Played: You may defeat a unit with 5 or less REMAINING HP (ObjectCurrentHP − Damage —
// buffs/upgrades raise it, damage lowers it). Fizzles with no qualifying unit.
$whenPlayedAbilities["SHD_050:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'may' => true,
        'extraFilter' => fn($o) => (ObjectCurrentHP($o) - intval($o->Damage ?? 0)) <= 5,
        'question' => "Defeat_a_unit_with_5_or_less_remaining_HP?", 'prompt' => "Defeat_a_unit_with_5_or_less_remaining_HP",
    ]);
};
