<?php
// TWI_070
// Cost 3 - Perilous Position - [Vigilance] - Upgrade Power -2 - Upgrade HP -2
// Text: When Played: Exhaust attached unit.

// TWI_070 Perilous Position — "When Played: Exhaust attached unit." (Upgrade -2/-2; $mzID = the host.)
$whenPlayedAbilities["TWI_070:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    OnExhaustCard(intval($player), $mzID);
};
