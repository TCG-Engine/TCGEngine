<?php
// ASH_250
// Cost 2 - Imperial Defector - [Heroism] - Power 3 - HP 2
// Text: When Played: Look at an opponent's hand.

// ASH_250 Imperial Defector — When Played: look at an opponent's hand. (Information only; the reveal is
// logged for the controller.)
$whenPlayedAbilities["ASH_250:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWULookAtOpponentHand(intval($player));
};
