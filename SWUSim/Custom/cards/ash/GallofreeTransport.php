<?php
// ASH_254
// Cost 4 - Gallofree Transport - [Heroism] - Power 3 - HP 5
// Text: When Defeated: Give 2 Advantage tokens to a friendly unit.

// ASH_254 Gallofree Transport — When Defeated: give 2 Advantage tokens to a friendly unit (the dying
// unit is already removed, so "a friendly unit" is a surviving friendly unit). Mandatory.
$whenDefeatedAbilities["ASH_254:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'token' => 'ADVANTAGE', 'amount' => 2,
        'prompt' => "Give_2_Advantage_tokens_to_a_friendly_unit",
    ]);
};
