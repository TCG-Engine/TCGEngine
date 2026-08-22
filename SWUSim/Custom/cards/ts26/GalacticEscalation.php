<?php
// TS26_56
// Cost 2 - Galactic Escalation - [Command]
// Text: Each player resources the top card of their deck.

$whenPlayedAbilities["TS26_56:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // "EACH player" — every live seat in player order. Was the caster + OtherPlayer() only.
    // An empty deck simply resources nothing for that seat; it does not stop the others.
    foreach (SWUSeatsInPlayerOrder(intval($player)) as $p) {
        if (!empty(GetDeck($p))) SWURampResourceExhausted($p, 'myDeck-0');
    }
};
