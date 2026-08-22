<?php
// JTL_221
// Cost 3 - Stolen AT-Hauler - [Cunning] - Power 4 - HP 5
// Text: When Defeated: Choose an opponent. For this phase, they may play this unit from its owner's discard pile for free.

// ── JTL_221 Stolen AT-Hauler — When Defeated ────────────────────────────────
// "When Defeated: Choose an opponent. For this phase, they may play this unit
//  from its owner's discard pile for free."
// In 2-player mode "choose an opponent" auto-resolves to the single opponent.
// If controller's opponent is the owner (card was stolen), owner gets TPF (own
// discard). Otherwise opponent gets OTPF (opponent's discard). Uses
// cardDiscardedHandlers (synchronous) so modifier is set before any DQ drain.
$cardDiscardedHandlers['JTL_221:0'] = function(int $player, object $entry, ?object $sourceObject = null): void {
    if ($entry->From !== 'PLAY') return;
    $controller = intval($sourceObject->Controller ?? $player);
    // ⚠ THIS HANDLER IS DELIBERATELY SYNCHRONOUS. cardDiscardedHandlers runs so the Modifier lands on the
    // discard entry BEFORE any decision-queue drain; queuing an interactive picker mid-handler would
    // reorder OpponentPlaysForFree. So the SOR_016 Thrawn precedent applies: branch on the seat count and
    // leave the 2-player path EXACTLY as it was.
    //
    //  ≤2 seats — "choose an opponent" is degenerate (one opponent). Stamp immediately, byte-identical:
    //             SetsOtpfOnDefeat's `MODIFIER:OTPF` and every other 2-player section stay untouched.
    //  >2 seats — a real choice. Stamp a PROVISIONAL grant to the first live opponent so the permission
    //             exists no matter what happens next (a defeat can resolve with nobody able to answer),
    //             then queue the picker to RE-STAMP it onto the seat the controller actually names.
    //             The re-stamp is idempotent and locates the entry by CardID within the owner's pile.
    $lone = SWUChooseOpponent($controller);
    $entry->Modifier = ($lone === $player) ? 'TPF' : SWUBuildDiscardModifier('OTPF', $lone);
    if (SeatCountForGame() <= 2) return;
    // ⚠ NO $eligible filter: any live opponent can be granted the free play. An opponent who cannot
    // currently afford to ACT on it is still a legal choice — the ruling notes they simply may not get to
    // use it ("An opponent can't play Stolen AT-Hauler if they can't take an action to play it").
    SWUQueueChooseOpponent($controller, 'JTL_221#0|' . $player . '|' . (string)($entry->CardID ?? 'JTL_221'),
        "Choose_an_opponent_who_may_play_it_for_free");
};

// Re-stamp the grant onto the CHOSEN seat. Runs after the synchronous handler above has already placed a
// provisional grant, so a declined/lost pick simply leaves that in place rather than losing the ability.
$customDQHandlers["JTL_221#0"] = function($player, $parts, $lastDecision) {
    $owner  = intval($parts[0] ?? 0);
    $cardID = (string)($parts[1] ?? 'JTL_221');
    $opp    = SWUPickedOpponent($lastDecision);
    if ($owner <= 0 || $opp <= 0) return;
    $d = &GetDiscard($owner);
    for ($i = count($d) - 1; $i >= 0; $i--) {          // most recent matching entry
        if (!empty($d[$i]->removed) || ($d[$i]->CardID ?? '') !== $cardID) continue;
        $cur = SWUParseDiscardModifier((string)($d[$i]->Modifier ?? ''));
        if ($cur['kind'] === '') break;                 // grant already expired (regroup cleared it)
        $d[$i]->Modifier = ($opp === $owner)
            ? 'TPF'
            : SWUBuildDiscardModifier('OTPF', $opp);
        break;
    }
    unset($d);
};
