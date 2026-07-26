<?php
// SHD_258
// Cost 3 - Mandalorian Warrior - Power 3 - HP 3
// Text: When Played: You may give an Experience token to another Mandalorian unit.

// ─── SHD_258 Mandalorian Warrior ──────────────────────────────────────────────
// When Played: You may give an Experience token to another Mandalorian unit. (Object-aware via
// TraitContains — a unit made Mandalorian by an upgrade, e.g. SHD_069 Foundling, is a valid target.)
$whenPlayedAbilities["SHD_258:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits' => 'Mandalorian', 'may' => true, 'excludeSelf' => true, 'friendlyOnly' => false,
        'question' => "Give_an_Experience_token_to_another_Mandalorian_unit?",
        'prompt'   => "Choose_a_Mandalorian_unit",
    ]);
};
