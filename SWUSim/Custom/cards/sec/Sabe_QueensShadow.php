<?php
// SEC_017
// Cost 5 - Sabé - Queen's Shadow - [Cunning,Heroism] - Power 3 - HP 6
// Text: When a friendly unit deals combat damage to a base: You may exhaust this leader. If you do, look at the top 2 cards of the defending player's deck. Discard 1 of those cards. (Put the other back on top.)
// DeployText: Raid 1 / When this unit deals combat damage to a base: Look at the defending player's hand. You may discard a card from it. If you do, that player draws a card.
// Epic Action: If you control 5 or more resources, deploy this leader.

$customDQHandlers["SEC_017#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || $mz === '-' || $mz === 'PASS') return;   // declined → no discard, no draw
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    // Derive the seat from the CHOSEN CARD's own mzID ("theirHand-N" at ≤2 seats, "p{n}Hand-N" above).
    // ⚠ This handler is SHARED with ASH_220 Remnant Lookouts, whose text is "an opponent" (a PICKER
    // card) while Sabé's is "the defending player" (DETERMINED by the board). Reading the seat off the
    // mzID is what lets one handler serve both without a branch: each caller builds the pool from the
    // seat IT decided, and the discard + draw follow the card that was actually picked.
    $opp    = SWUMzOwner($mz, intval($player));
    $cardID = $obj->CardID;
    $obj->Remove();
    SWUAddToDiscard($opp, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
    DoDrawCard($opp, 1);   // "If you do, that player draws a card."
};

$customDQHandlers["SEC_017#3"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SEC_017' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    // "the top 2 cards of THE DEFENDING PLAYER's deck" — the seat is named by the board, never chosen,
    // so this must NOT prompt. OtherPlayer() named one seat: above two seats Sabé milled a player who
    // was not in the combat (and seat 1 for any far-seat attacker).
    $opp  = SWUCurrentDefendingSeat(intval($player));
    $deck = &GetDeck($opp);
    $idx  = [];
    for ($i = 0; $i < count($deck) && count($idx) < 2; $i++) {
        if (empty($deck[$i]->removed)) $idx[] = $i;
    }
    if (empty($idx)) return;
    if (count($idx) === 1) {                                   // only 1 card → discard it
        $cid = $deck[$idx[0]]->CardID; $deck[$idx[0]]->Remove();
        SWUAddToDiscard($opp, $cid, 'DECK');
        DecisionQueueController::CleanupRemovedCards();
        return;
    }
    $c0 = $deck[$idx[0]]->CardID; $c1 = $deck[$idx[1]]->CardID; // 2 cards → choose which to discard
    DecisionQueueController::AddDecision($player, 'OPTIONCHOOSE', "{$c0}&{$c1}", 1, tooltip: "Discard_1_of_the_top_2_(other_stays_on_top)");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "SEC_017#4|{$opp}|{$c0}", 1);
};

// $parts[0] = opp, $parts[1] = the FIRST peeked CardID; $lastDecision = the chosen CardID to discard.
$customDQHandlers["SEC_017#4"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp     = intval($parts[0] ?? 0);
    $chosen  = $lastDecision ?? '';
    if ($opp <= 0 || $chosen === '') return;
    $deck = &GetDeck($opp);
    // Discard the FIRST top-2 deck entry whose CardID matches the chosen label; the other stays on top.
    for ($i = 0; $i < count($deck); $i++) {
        if (empty($deck[$i]->removed) && ($deck[$i]->CardID ?? '') === $chosen) {
            $deck[$i]->Remove();
            SWUAddToDiscard($opp, $chosen, 'DECK');
            break;
        }
    }
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', 'P' . intval($player) . " discarded " . GameLogCardRef($chosen) . " from P{$opp}'s deck");
};
