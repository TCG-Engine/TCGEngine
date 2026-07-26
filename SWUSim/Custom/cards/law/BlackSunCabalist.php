<?php
// LAW_249
// Cost 2 - Black Sun Cabalist - [Villainy] - Power 1 - HP 3
// Text: When Played: Give an Experience token to another friendly Underworld unit.

// LAW_249 Black Sun Cabalist — When Played: give an Experience token to another friendly Underworld unit.
$whenPlayedAbilities["LAW_249:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits' => 'Underworld', 'excludeSelf' => true,
        'prompt' => "Give_an_Experience_token_to_another_friendly_Underworld_unit",
    ]);
};
