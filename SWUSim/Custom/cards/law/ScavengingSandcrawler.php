<?php
// LAW_238
// Cost 4 - Scavenging Sandcrawler - [Cunning] - Power 1 - HP 7
// Text: On Attack: You may put a card from your discard pile on the bottom of your deck. If you do, create a Credit token.

// LAW_238 Scavenging Sandcrawler — On Attack: you may put a card from your discard on the bottom of
// your deck. If you do, create a Credit token.
$onAttackAbilities["LAW_238:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $disc = ZoneSearch("myDiscard");
    if (empty($disc)) return;
    SWUQueueMayChooseTarget(intval($player), $disc, "Put_a_card_from_discard_on_deck_bottom_to_create_a_Credit?", "Choose_a_card", "LAW_238#0");
};

$customDQHandlers["LAW_238#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom(intval($player), [$cardID]);
    SWUCreateCreditToken(intval($player), 1);
};
