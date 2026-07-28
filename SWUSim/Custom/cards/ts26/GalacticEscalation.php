<?php
// TS26_56
// Cost 2 - Galactic Escalation - [Command]
// Text: Each player resources the top card of their deck.

$whenPlayedAbilities["TS26_56:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    if (!empty(GetDeck(intval($player)))) SWURampResourceExhausted(intval($player), 'myDeck-0');
    if (!empty(GetDeck($opp)))            SWURampResourceExhausted($opp, 'myDeck-0');
};
