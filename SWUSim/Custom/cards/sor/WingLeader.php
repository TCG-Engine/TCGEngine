<?php
// SOR_241
// Wing Leader — When Played: give 2 Experience tokens to another friendly Rebel unit.
$whenPlayedAbilities["SOR_241:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits' => 'Rebel', 'amount' => 2, 'excludeSelf' => true,
        'prompt' => "Give_2_Experience_to_another_friendly_Rebel_unit",
    ]);
};
