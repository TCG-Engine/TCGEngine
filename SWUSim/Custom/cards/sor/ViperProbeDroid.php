<?php
// SOR_228
// Cost 2 - Viper Probe Droid - [Villainy] - Power 3 - HP 2
// Text: When Played: Look at an opponent's hand.

// SOR_228 Viper Probe Droid — "When Played: Look at an opponent's hand." (information only)
// Shows the opponent's hand to the player as an acknowledge popup (card images + an OK button).
$whenPlayedAbilities["SOR_228:0"] = function($player, $mzID) {
    SWULookAtOpponentHand(intval($player));      // logs the reveal (visible to both players for now)
    SWUQueueShowOpponentHand(intval($player));   // present the hand as an acknowledge popup
};
