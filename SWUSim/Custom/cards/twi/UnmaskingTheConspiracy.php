<?php
// TWI_223
// Cost 1 - Unmasking the Conspiracy - [Cunning]
// Text: Discard a card from your hand. If you do, look at an opponent's hand and discard a card from it.

// TWI_223 Unmasking the Conspiracy — own hand card chosen; discard it, then look at the opponent's hand
// and discard a card from it.
$customDQHandlers["TWI_223#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $o->Remove();
    SWUAddToDiscard(intval($player), $o->CardID, 'HAND'); // discard the chosen own card
    SWUOfferDiscard($player, ['from'=>'opp', 'prompt'=>"Discard_a_card_from_the_opponent's_hand"]);
};

// When Played (event) — migrated from OnPlayEvent. ($cardID hardcoded: the played event still sits
// in hand until block 10 and must be excluded from "discard a card from your hand".)
$whenPlayedAbilities["TWI_223:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();
    $hand = [];
    $excluded = false;
    foreach (array_values(ZoneSearch("myHand")) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (!$excluded && ($o->CardID ?? '') === 'TWI_223') { $excluded = true; continue; } // exclude this event
        $hand[] = $mz;
    }
    if (empty($hand)) return;
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand", "TWI_223#0");
};
