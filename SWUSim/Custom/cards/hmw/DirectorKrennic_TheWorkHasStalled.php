<?php
// HMW_061
// Cost 3 - Director Krennic - The Work Has Stalled - [Vigilance,Villainy] - Power 3 - HP 4 - Ground
//   Traits: Imperial, Official (unique)
// Text: On Attack: If your base is upgraded, draw a card.

// Reads the Fortify base-upgrade state through SWUBaseIsUpgraded, which is scoped to the attacker's OWN
// base — an upgrade on the opponent's base does not satisfy "your base". Combat owns the After Action.
$onAttackAbilities["HMW_061:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUBaseIsUpgraded(intval($player))) DoDrawCard(intval($player), 1);
};
