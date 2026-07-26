<?php
// TWI_193
// Cost 2 - R2-D2 - Full of Solutions - [Cunning,Heroism] - Power 2 - HP 4
// Text: When Played: You may discard a card from your hand. If you do, search the top 3 cards of your deck for a card and draw it. (Put the other cards on the bottom of your deck in a random order.)

// TWI_193 R2-D2 — "When Played: You may discard a card from your hand. If you do, search the top 3 cards
// of your deck for a card and draw it."
$whenPlayedAbilities["TWI_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) return;
    SWUQueueMayChooseTarget(intval($player), $hand,
        "You_may_discard_a_card_to_search_the_top_3", "Discard_a_card_from_your_hand", "TWI_193#0");
};

$customDQHandlers["TWI_193#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $o->Remove();
    SWUAddToDiscard(intval($player), $o->CardID, 'HAND'); // discard the chosen hand card
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 3, fn($c) => true, 1);
};
