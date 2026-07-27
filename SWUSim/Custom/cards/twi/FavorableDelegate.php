<?php
// TWI_208
// Cost 2 - Favorable Delegate - [Cunning] - Power 1 - HP 5
// Text: When Played: Draw a card. / When Defeated: Discard a card from your hand.

// TWI_208 Favorable Delegate — "When Played: Draw a card. When Defeated: Discard a card from your hand."
$whenPlayedAbilities["TWI_208:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};

$whenDefeatedAbilities["TWI_208:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();
    SWUOfferDiscard($player, ['from'=>'own']);
};
