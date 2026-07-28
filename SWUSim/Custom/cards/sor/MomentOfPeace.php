<?php
// SOR_073
// Moment of Peace
// Text: Give a Shield token to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_073:0"] = function($player, $mzID = '') {
    // Moment of Peace — "Give a Shield token to a unit."
    GiveTokenUpgrade($player, $mzID, ['token'=>'SHIELD','friendlyOnly'=>false,'prompt'=>"Give_a_Shield_token_to_a_unit"]);
            return;
};
