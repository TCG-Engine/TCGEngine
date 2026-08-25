<?php
// TWI_252
// Cost 2 - Aggrieved Parliamentarian - Power 2 - HP 2
// Text: When Played: Choose an opponent. They shuffle their discard pile and put it on the bottom of their deck.

// TWI_252 Aggrieved Parliamentarian — "When Played: Choose an opponent. They shuffle their discard pile
// and put it on the bottom of their deck." (2-player: the single opponent, resolved inline.)
$whenPlayedAbilities["TWI_252:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "CHOOSE AN OPPONENT" — a real choice above two seats; auto-resolves at one eligible (I1).
    // ⚠ ELIGIBILITY IS THE OPPOSITE OF TWI_222's, one card away in the same set. Here an opponent with an
    // EMPTY discard pile is a true no-op — shuffling nothing into nothing is a choice among nothing — so
    // they are filtered out. On TWI_222 an empty hand is a guaranteed payoff and must NOT be filtered.
    // Same "choose an opponent" sentence, opposite rule: decide eligibility per card, from what the
    // effect DOES to the chosen seat.
    $eligible = [];
    foreach (OpponentsOf(intval($player)) as $o) {
        foreach (GetDiscard($o) as $d) { if ($d !== null && empty($d->removed)) { $eligible[] = $o; break; } }
    }
    if (empty($eligible)) return;              // nobody to affect ⇒ no offer at all
    SWUQueueChooseOpponent(intval($player), 'TWI_252#0|' . intval($player),
        "Choose_an_opponent_to_shuffle_their_discard_away", $eligible);
};

$customDQHandlers["TWI_252#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $discard = &GetDiscard($opp);
    $cids = [];
    foreach ($discard as $d) { if ($d !== null && empty($d->removed)) $cids[] = $d->CardID; }
    if (empty($cids)) return;
    // Remove all from discard, shuffle, append to the bottom of the opponent's deck.
    foreach ($discard as $d) { if ($d !== null) $d->removed = true; }
    DecisionQueueController::CleanupRemovedCards();
    $playerID = $opp;
    _topDeckPutRemainingToBottom($opp, $cids);
    $playerID = intval($player);
};
