<?php
// SOR_200
// Spark of Rebellion
// Text: Look at an opponent's hand and discard a card from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_200:0"] = function($player, $mzID = '') {
// Spark of Rebellion — "Look at an opponent's hand and discard a card from it."
            global $playerID;
            $playerID = intval($player);
            $targets = SWULookAtOpponentHand(intval($player));   // any card is a valid target
            SWUQueueChooseTarget(intval($player), $targets, "Discard_a_card_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
            return;
};
