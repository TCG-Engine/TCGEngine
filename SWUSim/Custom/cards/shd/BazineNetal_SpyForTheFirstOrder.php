<?php
// SHD_184
// Cost 2 - Bazine Netal - Spy for the First Order - [Cunning,Villainy] - Power 1 - HP 3
// Text: When Played: Look at an opponent's hand. You may discard 1 of those cards. If you do, that player draws a card. / Smuggle [4 resources Cunning Villainy]

// ─── SHD_184 Bazine Netal ─────────────────────────────────────────────────────
// When Played: Look at an opponent's hand. You may discard 1 of those cards. If you do, that
// player draws a card. (Her old listing in the JTL_111 draw-reaction hook was a mis-mapping —
// removed.)
$whenPlayedAbilities["SHD_184:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "Look at AN OPPONENT's hand" — the caster picks whose. Auto-resolves to an invisible PASSPARAMETER
    // at one eligible opponent, so Premier is byte-identical (I1).
    // ⚠ FILTER to opponents holding a card: with an empty hand there is nothing to look at, nothing to
    // discard and the rider never fires — a choice among nothing.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;                  // no hand anywhere ⇒ no offer at all
    SWUQueueChooseOpponent(intval($player), 'SHD_184#1|' . intval($player),
        "Choose_an_opponent_whose_hand_to_look_at", $eligible);
};

$customDQHandlers["SHD_184#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    // Pass the picked seat to the helper. Two things depend on it: the hand actually READ, and the mzID
    // FORM the helper emits — "theirHand-N" at ≤2 seats, "p{n}Hand-N" above, which is what the transport's
    // hidden-zone reveal needs in order to show the cards at all (a legacy "theirHand" Param above two
    // seats renders as CARD BACKS and the player picks blind).
    $targets = SWULookAtOpponentHand($caster, null, $opp);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget($caster, $targets,
        "Discard_1_of_those_cards?", "Discard_1_of_those_cards?", "SHD_184#0");
};

$customDQHandlers["SHD_184#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    // The discarded card's own mzID names its owner ("theirHand-N" at ≤2 seats, "p{n}Hand-N" above), so
    // the discard, the log line and the "that player draws" rider all follow the card actually picked.
    $opp    = SWUMzOwner((string)$lastDecision, intval($player));
    if ($opp <= 0 || $opp === intval($player)) return;
    $cardID = $obj->CardID;
    $obj->Remove();
    SWUAddToDiscard($opp, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
    DoDrawCard($opp, 1);   // "If you do, that player draws a card."
};
