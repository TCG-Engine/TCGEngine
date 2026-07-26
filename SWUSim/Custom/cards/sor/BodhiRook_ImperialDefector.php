<?php
// SOR_201
// Cost 3 - Bodhi Rook - Imperial Defector - [Cunning,Cunning] - Power 3 - HP 3
// Text: When Played: Look at an opponent's hand and discard a non-unit card from it.

// SOR_201 Bodhi Rook — "When Played: Look at an opponent's hand and discard a non-unit card from it."
$whenPlayedAbilities["SOR_201:0"] = function($player, $mzID) {
    $targets = SWULookAtOpponentHand(intval($player), fn($cid) => stripos(CardType($cid) ?? '', 'unit') === false);
    // Queue the discard first: with 2+ legal targets it's an MZCHOOSE (which already presents the
    // hand); with 0 or 1 it auto-resolves with no choice, so the player never sees the hand. In that
    // no-MZCHOOSE case, SAVE a snapshot of the hand NOW (still pre-discard, since the queued discard
    // hasn't executed yet) and show it Viper-Probe-Droid style (SOR_228) AFTER the auto-discard
    // resolves — the saved snapshot still shows the discarded card so the player can confirm OK.
    SWUQueueChooseTarget(intval($player), $targets, "Discard_a_non-unit_card_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
    if (count($targets) <= 1) SWUQueueShowOpponentHand(intval($player));
};
