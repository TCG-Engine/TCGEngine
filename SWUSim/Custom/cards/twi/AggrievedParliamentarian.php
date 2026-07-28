<?php
// TWI_252
// Cost 2 - Aggrieved Parliamentarian - Power 2 - HP 2
// Text: When Played: Choose an opponent. They shuffle their discard pile and put it on the bottom of their deck.

// TWI_252 Aggrieved Parliamentarian — "When Played: Choose an opponent. They shuffle their discard pile
// and put it on the bottom of their deck." (2-player: the single opponent, resolved inline.)
$whenPlayedAbilities["TWI_252:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
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
