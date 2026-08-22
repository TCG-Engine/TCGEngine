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
// CLAUSE 2 — "attached base gains: 'You may look at the top card of your deck at any time'" is a
// CONTINUOUS VISIBILITY permission: not a triggered ability, not an Action, just a standing right to see
// one hidden card. It needs NO code here. The permission is a derived predicate, not stored state:
// _SWUCanSeeOwnTopCard() in GameLogic.php asks the board who currently grants it (this upgrade on your
// base, or a LAW_094 Hondo Ohnaka you control), so it follows the base and cannot go stale when the
// upgrade is defeated. The generator wires that predicate into the per-viewer transport, emitting the top
// card ONLY to the entitled seat. Tests: Tests/Cases/core/LookAtTopCardPermission.md (the predicate, via
// the P#SEESTOPCARD assertion added for this) — the no-leak half is verified against a live GetNextTurn,
// which the in-process runner cannot see because it renders no transport.
//
// ⚠ The When Played is SHD_184 Bazine Netal's clause word for word, and uses the same shape: the
// pending theirHand MZMAYCHOOSE IS the reveal the client renders (that is what "look at" resolves to
// here), and SWULookAtOpponentHand additionally writes the private log line.
$whenPlayedAbilities["HMW_205:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // "Look at AN OPPONENT's hand" — the caster picks whose. Auto-resolves invisibly at one eligible (I1).
    // ⚠ FILTER to opponents holding a card: an empty hand has nothing to look at and nothing to discard.
    // ⚠ PREVIEW-SET ASSUMPTION, FLAGGED DELIBERATELY: this card is not in
    //    `.claude/SWUSim/refs/card-specific-rulings.md` — that database covers RELEASED sets only, so
    //    there is no HMW/IC27 entry to cite. The reading is taken from the closest released analogue,
    //    which here is EXACT: SHD_184 Bazine Netal prints this clause WORD FOR WORD and does carry the
    //    ruling "If there are multiple opponents, the controlling player chooses which one will be
    //    'an opponent.'" Re-check when the set is released and the database is refreshed.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'HMW_205#1',
        "Choose_an_opponent_whose_hand_to_look_at", $eligible);
};

$customDQHandlers["HMW_205#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    // Passing $opp also makes the helper emit p{n}Hand-N above two seats — the form the transport's
    // hidden-zone reveal needs, or the hand renders as CARD BACKS.
    $targets = SWULookAtOpponentHand(intval($player), null, $opp);   // logs the reveal, returns the mzIDs
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
    // The chosen card's own mzID names its owner, so the discard, the log line and the "they draw"
    // rider all follow the card actually picked.
    $opp    = SWUMzOwner((string)$lastDecision, intval($player));
    if ($opp <= 0 || $opp === intval($player)) return;
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
