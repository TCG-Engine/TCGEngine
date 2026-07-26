<?php
// LAW_192
// Cost 3 - Bracca Shipbreaker - [Aggression] - Power 4 - HP 3
// Text: On Attack: Discard a card from your deck.

// LAW_192 Bracca Shipbreaker — On Attack: discard a card from your deck.
$onAttackAbilities["LAW_192:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUMillTopCard(intval($player));
};
