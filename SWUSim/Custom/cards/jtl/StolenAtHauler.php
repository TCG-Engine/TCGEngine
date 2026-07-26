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
    if ($entry->From === 'PLAY') {
        $controller = intval($sourceObject->Controller ?? $player);
        $opponent = OtherPlayer($controller);
        $entry->Modifier = ($opponent === $player) ? 'TPF' : 'OTPF';
    }
};
