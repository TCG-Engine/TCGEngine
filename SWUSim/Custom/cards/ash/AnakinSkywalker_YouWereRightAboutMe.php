<?php
// ASH_255
// Cost 5 - Anakin Skywalker - You Were Right About Me - [Heroism] - Power 6 - HP 4
// Text: Hidden / Saboteur / When Played: Give a Shield token to another friendly unit.

// ASH_255 Anakin Skywalker — Hidden + Saboteur (keywords) + When Played: give a Shield token to another
// friendly unit.
$whenPlayedAbilities["ASH_255:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, ['token'=>'SHIELD','excludeSelf'=>true,'prompt'=>"Give_a_Shield_token_to_another_friendly_unit"]);
};
