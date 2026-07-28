<?php
// SOR_200
// Spark of Rebellion
// Text: Look at an opponent's hand and discard a card from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_200:0"] = function($player, $mzID = '') {
// Spark of Rebellion — "Look at an opponent's hand and discard a card from it."
            SWUOfferDiscard($player, ['from'=>'opp', 'prompt'=>"Discard_a_card_from_the_opponent's_hand"]);
            return;
};
