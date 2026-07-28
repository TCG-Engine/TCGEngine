<?php
// ASH_220
// Cost 3 - Remnant Lookouts - [Cunning] - Power 3 - HP 3
// Text: When Played: Look at an opponent's hand. You may discard a card from it. If you do, they draw a card.

// ASH_220 Remnant Lookouts — When Played: look at an opponent's hand; you may discard a card from it. If
// you do, they draw a card. Identical to SEC_017's deployed base-hit effect, so it reuses SEC_017#2.
$whenPlayedAbilities["ASH_220:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $cards = SWULookAtOpponentHand(intval($player));   // logs the private reveal + returns theirHand-N
    if (empty($cards)) return;
    SWUQueueMayChooseTarget(intval($player), $cards, "Discard_a_card_from_the_opponent's_hand?", "Choose_a_card", "SEC_017#2");
};
