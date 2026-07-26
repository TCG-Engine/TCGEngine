<?php
// LAW_026
// Shipbreaking Yard - [Aggression] - HP 26
// Text: 
// Epic Action: Discard 3 cards from your deck. You may return a card discarded this way to the top of your deck.

// LAW_026 Shipbreaking Yard — put the chosen milled card on the top of the deck.
$customDQHandlers["LAW_026#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cid = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    $deck = &GetDeck(intval($player));
    $newTop = new Deck($cid, 'Deck', intval($player));
    array_unshift($deck, $newTop);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
};

// LAW_026 Shipbreaking Yard — Epic Action: Discard 3 cards from your deck. You may return a card
// discarded this way to the top of your deck.
$baseAbilities["LAW_026"] = function($player) {
    global $playerID; $playerID = intval($player);
    $milledMz = [];
    for ($i = 0; $i < 3; $i++) {
        $c = SWUMillTopCard(intval($player));
        if ($c === null) break;
        $disc = array_values(ZoneSearch("myDiscard"));
        if (!empty($disc)) $milledMz[] = end($disc);  // the just-milled card is the newest discard entry
    }
    if (empty($milledMz)) { SWUAfterAction($player); return; }
    SWUQueueMayChooseTarget(intval($player), $milledMz, "Return_a_discarded_card_to_the_top_of_your_deck?", "Choose_a_card", "LAW_026#0");
    SWUQueueAfterAction($player);
};
