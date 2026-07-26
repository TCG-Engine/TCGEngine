<?php
// SEC_230
// Cost 2 - Charged with Espionage - [Cunning]
// Text: You may disclose CunningCunning (reveal cards from your hand with these aspect icons among them). If you do, look at an opponent's hand and discard a unit from it.

// SEC_230 Charged with Espionage — disclose succeeded → look at the opponent's hand, discard a unit.
$customDQHandlers["SEC_230#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $units = SWULookAtOpponentHand(intval($player), fn($cid) => stripos(CardType($cid) ?? '', 'unit') !== false);
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Discard_a_unit_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_230:0"] = function($player, $mzID = '') {
// Charged with Espionage — "You may disclose CunningCunning → look at an
                          // opponent's hand and discard a UNIT from it."
            SWUQueueDisclose(intval($player), ['Cunning', 'Cunning'], "SEC_230#0",
                "Disclose_CunningCunning_to_discard_a_unit_from_an_opponent's_hand");
            return;
};
