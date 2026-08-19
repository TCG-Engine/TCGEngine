<?php
// HMW_205
// Cost 1 - Intelligence Agency - [Cunning,Villainy] - Upgrade - Trait: Fortification
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains: "You may look at the top card of your deck at any time."
//       When Played: Look at an opponent's hand. You may discard a card from it. If you do, they draw a card.
//
// FORTIFY needs no code — HMW_205 is in $Fortify_Cards and SWUGetUpgradeValidTargets routes a Fortify
// upgrade to ['myBase-0']. (Proven behaviourally in the tests via HMW_066 Carrion Spike, which reads
// SWUBaseUpgradeCount and gains +1/+0 the moment this lands on the base.)
//
// ⚠⚠ CLAUSE 2 IS DELIBERATELY UNIMPLEMENTED — "attached base gains: 'You may look at the top card of
// your deck AT ANY TIME'" is a CONTINUOUS VISIBILITY permission: not a triggered ability, not an Action,
// just a standing right to see one hidden card. The engine has no such capability (nothing sets or reads
// a per-player top-card-visibility flag anywhere), and the harness has ZERO visibility assertions, so it
// could not be tested even if it were built — a GetNextTurn per-viewer payload check is the only thing
// that could see it. This is the same documented family as LAW_094 Hondo's visibility clause: a
// deferral with a written reason, NOT a silent omission. Implementing it means (a) a per-player flag,
// (b) GetNextTurn emitting the top card to the entitled seat only, and (c) a client affordance —
// a UI/transport feature, not a card fix.
//
// ⚠ The When Played is SHD_184 Bazine Netal's clause word for word, and uses the same shape: the
// pending theirHand MZMAYCHOOSE IS the reveal the client renders (that is what "look at" resolves to
// here), and SWULookAtOpponentHand additionally writes the private log line.
$whenPlayedAbilities["HMW_205:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = SWULookAtOpponentHand(intval($player));   // logs the reveal, returns theirHand-N
    // An EMPTY opponent hand means there is nothing to look at and nothing to offer, so no prompt is
    // raised at all — the SEC_186 / SEC_210 / SEC_260 family (never ask a question with no answer).
    // ⚠ Measured redundant (SWUQueueMayChooseTarget already no-ops on an empty pool) — kept as a local
    // statement of intent, and it starts mattering if that helper ever changes.
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Discard_a_card_from_their_hand?", "Discard_a_card_from_their_hand?", "HMW_205#0");
};

$customDQHandlers["HMW_205#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;      // "you MAY" — declining does neither half
    $obj = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($obj)) return;
    $opp    = OtherPlayer(intval($player));
    $cardID = (string)$obj->CardID;
    $obj->Remove();
    // The card is the OPPONENT'S, so it goes to THEIR discard pile, stamped From=HAND (which is what the
    // when-discarded observers gate on).
    SWUAddToDiscard($opp, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD',
        'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
    // "If you do, THEY draw a card" — gated on the discard above actually having happened, and it is the
    // opponent who draws. On an empty deck this resolves as the CR 6.1 deck-out penalty, not a no-op.
    DoDrawCard($opp, 1);
};
