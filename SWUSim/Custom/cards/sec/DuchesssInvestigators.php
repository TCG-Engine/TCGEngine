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
    _SWUOpponentDiscardRandom(intval($player));
};
