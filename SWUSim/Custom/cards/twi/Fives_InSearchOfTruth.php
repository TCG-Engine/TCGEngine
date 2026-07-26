<?php
// TWI_216
// Cost 5 - Fives - In Search of Truth - [Cunning] - Power 5 - HP 5
// Text: Saboteur / When you play an event: You may put a Clone unit from your discard pile on the bottom of your deck. If you do, draw a card.

$customDQHandlers["TWI_216#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cid = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom(intval($player), [$cid]);
    DoDrawCard(intval($player), 1);
};
