<?php
// SHD_040
// Cost 2 - Clan Wren Rescuer - [Heroism,Vigilance] - Power 1 - HP 2
// Text: When Played: Give an Experience token to a unit.

// ─── SHD_040 Clan Wren Rescuer ────────────────────────────────────────────────
// When Played: Give an Experience token to a unit (mandatory; the Rescuer itself is a valid target).
$whenPlayedAbilities["SHD_040:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'friendlyOnly' => false,
        'prompt' => "Give_an_Experience_token_to_a_unit",
    ]);
};
