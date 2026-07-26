<?php
// LAW_121
// Cost 5 - Canto Bight Security - [Vigilance] - Power 3 - HP 5
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / On Defense: Create a Credit token.

// LAW_121 Canto Bight Security — Sentinel + On Defense: Create a Credit token. (Non-interactive;
// $player = the defending unit's controller.)
$onDefenseAbilities["LAW_121:0"] = function($player, $mzID) {
    SWUCreateCreditToken(intval($player), 1);
};
