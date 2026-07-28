<?php
// LAW_142
// Cost 1 - Scarif Lieutenant - [Command,Heroism] - Power 2 - HP 1
// Text: When Defeated: Give an Experience token to a friendly Rebel unit.

// LAW_142 Scarif Lieutenant — When Defeated: give an Experience token to a friendly Rebel unit.
// (Object-aware — SEC_156/LAW_150 granted-Rebel units are valid targets.)
$whenDefeatedAbilities["LAW_142:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits' => 'Rebel',
        'prompt' => "Give_an_Experience_token_to_a_friendly_Rebel_unit",
    ]);
};
