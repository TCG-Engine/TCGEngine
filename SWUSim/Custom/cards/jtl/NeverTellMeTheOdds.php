<?php
// JTL_208
// Never Tell Me the Odds
// Text: Discard 3 cards from an opponent's deck and 3 cards from your deck. Deal damage to a unit equal to the number of cards with an odd cost discarded this way.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_208:0"] = function($player, $mzID = '') {
// Cunning — "Discard 3 cards from an opponent's deck and 3 cards from your deck.
                          // Deal damage to a unit equal to the number of cards with an odd cost discarded
                          // this way."
            global $playerID;
            $playerID = intval($player);
            // "Discard 3 cards from AN OPPONENT's deck and 3 from your deck." The caster picks whose.
            // ⚠⚠ NO $eligible FILTER, AND THE FIX ITSELF IS THE HAZARD HERE. SWUQueueChooseOpponent queues
            // NOTHING when the eligible list is empty, and the SELF-mill plus the damage live AFTER the
            // pick — so filtering on "opponents with cards in deck" would silently lose your own mill and
            // your own damage in a 2-PLAYER game against an empty deck. That is a Premier regression
            // introduced by the fix, not by the bug, and no pre-existing section covers it.
            // An empty-deck opponent is also a legal, meaningful pick: your 3 still mill and the odd-cost
            // count still comes off your own cards.
            SWUQueueChooseOpponent(intval($player), 'JTL_208#0',
                "Choose_an_opponent_to_mill_3_from");
            return;
};

$customDQHandlers["JTL_208#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    $odd = 0;
    for ($i = 0; $i < 3; $i++) { $c = SWUMillTopCard($opp);            if ($c !== null && (intval(CardCost($c)) % 2) === 1) $odd++; }
    for ($i = 0; $i < 3; $i++) { $c = SWUMillTopCard(intval($player)); if ($c !== null && (intval(CardCost($c)) % 2) === 1) $odd++; }
    if ($odd <= 0) return;
    // 'side' => 'any' is CORRECT and must NOT be narrowed: "a unit" is unqualified, so it spans every
    // seat's board including your own. Contrast JTL_125, whose pool is scoped to "that opponent".
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $odd, 'side' => 'any',
        'prompt' => "Deal_{$odd}_damage_to_a_unit",
    ]);
};
