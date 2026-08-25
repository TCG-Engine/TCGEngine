<?php
// SOR_185
// Cost 8 - Chimaera - Flagship of the Seventh Fleet - [Cunning,Villainy] - Power 8 - HP 7
// Text: Shielded (When you play this unit, give a Shield token to it.) / On Attack: Name a card. An opponent reveals their hand and discards a card with that name from it.

// SOR_185 Chimaera — "On Attack: Name a card. An opponent reveals their hand and discards a card
// with that name from it." First server-side consumer of the NAMECARD decision.
$onAttackAbilities["SOR_185:0"] = function($player, $mzID) {
    // "Name a card. AN OPPONENT reveals their hand and discards a card with that name from it."
    // ⚠ THE PICKER MUST COME FIRST, and for a hard transport reason as well as a game one: a card TITLE
    // CONTAINS SPACES, and a DecisionQueue Param row is SPACE-DELIMITED — so the name can only ever
    // travel in $lastDecision, never in a Param. That means NAMECARD has to be the LAST decision in the
    // chain, and anything else the handler needs (the seat) must already be in its Param.
    // ⚠ FILTER to opponents holding a card: an empty hand reveals nothing and discards nothing.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'SOR_185#1',
        "Choose_an_opponent_to_reveal_their_hand", $eligible);
};

$customDQHandlers["SOR_185#1"] = function($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_185#0|" . $opp, 1);
};

// Receives the named card NAME ($lastDecision is the card title, e.g. "Mission Briefing"). The
// opponent reveals their hand (public), then discards ONE card whose title matches that name.
$customDQHandlers["SOR_185#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $namedName = trim($lastDecision);
    $opp       = intval($parts[0] ?? 0);
    if ($opp <= 0 || $opp === intval($player)) return;
    $oppHand   = &GetHand($opp);

    // "An opponent reveals their hand" — public reveal of the whole hand.
    $refs = [];
    foreach ($oppHand as $card) { if (empty($card->removed)) $refs[] = GameLogCardRef($card->CardID); }
    AddGameLogEntry('REVEAL', "P{$opp} revealed their hand: " . (empty($refs) ? '(empty)' : implode(', ', $refs)), 'ALL');
    AddGameLogEntry('NAMECARD', 'P' . intval($player) . ' named ' . $namedName, 'ALL');

    // Show the opponent's hand to the player as an acknowledge popup (SOR_201 Bodhi Rook style).
    // Queue it BEFORE the inline discard so the snapshot captures the PRE-discard hand; the popup
    // resolves AFTER this handler returns, so on a hit the player sees the discarded card to confirm,
    // and on a whiff (no match) they see the full unchanged hand. The discard always auto-resolves
    // (copies are identical → no MZCHOOSE), so this is the only way the player would ever see the hand.
    SWUQueueShowOpponentHand(intval($player), $opp);

    // "discards a card with that name from it" — the first matching copy (by card title).
    foreach ($oppHand as $card) {
        if (empty($card->removed) && SWUObjectTitle($card) === $namedName) {
            $cid = $card->CardID;
            $card->Remove();
            SWUAddToDiscard($opp, $cid, 'HAND');
            DecisionQueueController::CleanupRemovedCards();
            AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cid) . " from P{$opp}'s hand", 'ALL');
            break;
        }
    }

    // SEC_016 Padmé Amidala — "When you reveal or discard 1 or more cards from YOUR hand." Both halves of
    // this ability happen to the OPPONENT's hand, so the react fires for THEM ($opp), not the attacker.
    // It fires once for the whole event even when a card is also discarded (one reveal-or-discard event,
    // not two). This path did neither: the reveal was only a log line, and the discard is a raw Remove()
    // that bypasses DoDiscardCard's observers. No-op when the opponent has no Padmé in play.
    if (!empty($refs) && function_exists('_SWUSec016React')) {
        $savedPID = $playerID;
        _SWUSec016React($opp);
        $playerID = $savedPID;
    }
};
