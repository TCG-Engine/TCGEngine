<?php
// ASH_157
// Cost 4 - Danger Squadron Wingmen - [Aggression,Heroism] - Power 4 - HP 5
// Text: On Attack: You may give an Advantage token to another unit.

// ASH_157 Danger Squadron Wingmen — On Attack: you may give an Advantage token to another unit.
$onAttackAbilities["ASH_157:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'token' => 'ADVANTAGE', 'may' => true, 'excludeSelf' => true, 'friendlyOnly' => false,
        'question' => "Give_an_Advantage_token_to_another_unit?", 'prompt' => "Choose_a_unit",
    ]);
};
