<?php
// SEC_186
// Cost 2 - Garindan - Information Broker - [Cunning,Villainy] - Power 1 - HP 3
// Text: When Played: Name a card. Look at an opponent's hand and discard a card with that name from it. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_186 Garindan — When Played: name a card; look at an opponent's hand and discard a card with
// that name from it.
$whenPlayedAbilities["SEC_186:0"] = function($player, $mzID) {
    // If the opponent's hand is empty there is nothing to look at or discard, so the naming is
    // meaningless — skip the whole ability (no NAMECARD prompt).
    // "Name a card. Look at AN OPPONENT's hand and discard a card with that name from it."
    // ⚠ ORDER: the opponent is picked FIRST, before the NAMECARD. You cannot look at "an opponent's" hand
    // until one is named, and naming a card blind at a hand you have not chosen is a different (worse)
    // game action. The existing 2-player prompt sequence is unchanged because the picker auto-resolves.
    // ⚠ FILTER to opponents holding a card: an empty hand has nothing to look at and nothing to discard,
    // so naming a card against it is meaningless — which is exactly the skip this card already had.
    global $playerID;
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'SEC_186#1',
        "Choose_an_opponent_whose_hand_to_look_at", $eligible);
};

$customDQHandlers["SEC_186#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    DecisionQueueController::AddDecision(intval($player), "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_186#0|" . $opp, 1);
};

$customDQHandlers["SEC_186#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $savedPID = $playerID;
    $named = trim($lastDecision);
    $opp = intval($parts[0] ?? 0);
    if ($opp <= 0 || $opp === intval($player)) return;
    // "Look at an opponent's hand AND discard a card with that name from it." The look is its own
    // clause and the player is entitled to it whether or not the named card is actually there — which
    // is most of the time, since the name is chosen blind. This card raised no hand-facing decision at
    // all, so the hand was never shown; same gap as Beguile. Queued BEFORE the discard so the popup
    // shows the hand as it was looked at, including the card about to be taken.
    // Pass the picked seat: it selects the hand read AND makes the helper emit p{n}Hand-N above two
    // seats, which is the form the transport's hidden-zone reveal needs (else the row is card backs).
    SWULookAtOpponentHand(intval($player), null, $opp);
    SWUQueueShowOpponentHand(intval($player), $opp);
    $playerID = $opp;
    foreach (ZoneSearch("myHand", null) as $mz) {       // opponent's hand, in opp frame
        $c = GetZoneObject($mz);
        if ($c !== null && empty($c->removed) && SWUObjectTitle($c) === $named) {
            $c->Remove();
            SWUAddToDiscard($opp, $c->CardID, 'HAND');
            break;                                       // discard one matching card
        }
    }
    $playerID = $savedPID;
};
