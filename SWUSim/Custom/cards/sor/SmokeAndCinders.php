<?php
// SOR_174
// Cost 5 - Smoke and Cinders - [Aggression]
// Text: Each player discards all but 2 cards (of their choice) from their hand.

// SOR_174 Smoke and Cinders — discard every card in $parts[0]'s hand NOT among the kept mzIDs
// ($lastDecision, &-delimited). Snapshot the discard set before any removal (mark removed, then one
// cleanup) so indices stay valid through the loop. See SWUKeepNDiscardRest (which built the spec).
$customDQHandlers["SOR_174#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $p = intval($parts[0] ?? $player);
    $playerID = $p;
    $keptSet = [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        foreach (explode("&", $lastDecision) as $m) { if ($m !== '') $keptSet[$m] = true; }
    }
    $hand = &GetHand($p);
    $toDiscard = [];
    foreach ($hand as $idx => $card) {
        if (isset($card->removed) && $card->removed) continue;
        if (isset($keptSet["myHand-{$idx}"])) continue;
        $toDiscard[] = $card;
    }
    foreach ($toDiscard as $card) {
        $cid = $card->CardID;
        $card->removed = true;
        SWUAddToDiscard($p, $cid, 'HAND');
    }
    DecisionQueueController::CleanupRemovedCards();
    // SEC_016 Padmé "when you discard 1+ cards from your hand" — fire ONCE (collective) for this player's
    // batch. (SOR_174 runs this handler once per player, so each player's own Padmé triggers correctly.)
    if (!empty($toDiscard) && function_exists('_SWUSec016React')) _SWUSec016React($p);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_174:0"] = function($player, $mzID = '') {
// Smoke and Cinders — "Each player discards all but 2 cards (of their choice)
                          // from their hand." Queue the opponent's keep-2 first, then the caster's, so
                          // $playerID is left = caster (whose MZMULTICHOOSE is validated first, in their
                          // own queue). Each player's decision is answered under their own $playerID.
            // "EACH player" is every LIVE seat, not just the one opponent: OtherPlayer() left Twin
            // Suns seats 3 and 4 holding full hands. OpponentsOf() already filters to live seats, and
            // returns exactly [the other seat] in a 2-player game, so that case is unchanged.
            // Caster LAST so $playerID is left on them (see the note above).
            $me = intval($player);
            foreach (OpponentsOf($me) as $opp) {
                SWUKeepNDiscardRest($opp, 2, "Keep_2_cards_-_discard_the_rest");
            }
            SWUKeepNDiscardRest($me, 2, "Keep_2_cards_-_discard_the_rest");
            return;
};
