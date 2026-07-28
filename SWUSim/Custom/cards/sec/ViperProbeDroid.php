<?php
// SEC_239
// Cost 2 - Viper Probe Droid - [Villainy] - Power 3 - HP 2
// Text: When Played: Look at an opponent's hand.

// SEC_239 Viper Probe Droid — When Played: look at an opponent's hand. (Informational only — no game
// state change in the sim; logged for provenance.)
$whenPlayedAbilities["SEC_239:0"] = function($player, $mzID) {
    AddGameLogEntry('ABILITY', 'P' . intval($player) . " looked at an opponent's hand");
};
