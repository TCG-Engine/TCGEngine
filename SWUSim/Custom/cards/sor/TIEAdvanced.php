<?php
// SOR_231
// TIE Advanced — When Played: give 2 Experience tokens to another friendly Imperial unit.
$whenPlayedAbilities["SOR_231:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits' => 'Imperial', 'amount' => 2, 'excludeSelf' => true,
        'prompt' => "Give_2_Experience_to_another_friendly_Imperial_unit",
    ]);
};
