<?php
// SEC_197
// Cost 1 - Furtive Handmaiden - [Cunning,Heroism] - Power 2 - HP 2
// Text: On Attack: You may discard a card from your hand. If you do, draw a card.

// SEC_197 Furtive Handmaiden — On Attack: you may discard a card from your hand. If you do, draw a card.
$onAttackAbilities["SEC_197:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = ZoneSearch("myHand");
    if (empty($hand)) return;
    SWUQueueMayChooseTarget(intval($player), $hand, "Discard_a_card_to_draw_a_card?", "Choose_a_card_to_discard", "SEC_197#0");
};

$customDQHandlers["SEC_197#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    DoDiscardCard(intval($player), $lastDecision);
    DoDrawCard(intval($player), 1);
};
