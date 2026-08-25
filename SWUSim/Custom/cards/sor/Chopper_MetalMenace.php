<?php
// SOR_188
// Cost 1 - Chopper - Metal Menace - [Cunning,Heroism] - Power 1 - HP 3
// Text: While you control another SPECTRE unit, this unit gains Raid 1. / On Attack: Discard a card from the defending player's deck. If it's an event, exhaust a resource that player controls.

// SOR_188 Chopper — "On Attack: Discard a card from the defending player's deck. If it's an event,
// exhaust a resource that player controls." (Conditional Raid 1 lives in GetConditionalKeyword_Raid_Value.)
$onAttackAbilities["SOR_188:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // "THE DEFENDING PLAYER" — named by the board, never chosen, so this must not prompt.
    // Was GetOpponent(), the worst of the three legacy helpers: `1→2, 2→1, else NULL`. Two failures in
    // one line — a seat-1/2 Chopper attacking a FAR seat milled and exhausted an UNINVOLVED player while
    // the real defender was untouched, and a Chopper CONTROLLED by seat 3 or 4 produced null, which
    // SWUMillTopCard(int $player) cannot coerce (a fatal, not a quiet no-op).
    $defender = SWUCurrentDefendingSeat(intval($player));
    // No attack in flight (or a base-less/unknown defender) ⇒ do nothing. Deliberately NOT falling back
    // to OtherPlayer()/GetOpponent(): guessing a seat is the bug class this card is being fixed for.
    if ($defender <= 0 || $defender === intval($player)) return;
    $milled = SWUMillTopCard($defender);
    if ($milled === null) return;
    if (strpos(CardType($milled) ?? '', 'Event') !== false) {
        SWUExhaustResources($defender, 1); // exhaust a resource the defending player controls
    }
};
