<?php
// SEC_223
// Cost 5 - Duchess's Investigators - [Cunning] - Power 4 - HP 4
// Text: When Played: You may disclose Cunning (reveal a card from your hand with this aspect icon). If you do, each opponent discards a random card from their hand.

// SEC_223 Duchess's Investigators — When Played: you may disclose Cunning → each opponent discards a
// random card. (2-player: the one opponent.)
$whenPlayedAbilities["SEC_223:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Cunning'], "SEC_223#0",
        "Disclose_Cunning_to_make_each_opponent_discard_at_random");
};

$customDQHandlers["SEC_223#0"] = function($player, $parts, $lastDecision) {
    // ⚠ "EACH OPPONENT discards a random card" — one call per opponent. The shared helper defaults to
    // OtherPlayer(), so before this only seat 2 ever discarded. OpponentsOf() is length-1 at two seats,
    // so Premier and 1v1 Twin Suns are byte-identical.
    foreach (OpponentsOf(intval($player)) as $opp) {
        _SWUOpponentDiscardRandom(intval($player), $opp);
    }
};
