<?php
// ASH_220
// Cost 3 - Remnant Lookouts - [Cunning] - Power 3 - HP 3
// Text: When Played: Look at an opponent's hand. You may discard a card from it. If you do, they draw a card.

// ASH_220 Remnant Lookouts — When Played: look at an opponent's hand; you may discard a card from it. If
// you do, they draw a card. Identical to SEC_017's deployed base-hit effect, so it reuses SEC_017#2.
$whenPlayedAbilities["ASH_220:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ "Look at AN OPPONENT'S hand" — an opponent OF YOUR CHOICE. Passing no seat makes
    // SWULookAtOpponentHand fall back to SWUChooseOpponent, which AUTO-PICKS the first live opponent —
    // the sweep's original placeholder. Filtered to opponents actually HOLDING a card (nothing to look at
    // is a choice among nothing) and auto-resolving at one, so Premier is byte-identical. Pattern: SHD_184
    // Bazine Netal, the canonical analogue for this clause.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'ASH_220#LOOK', "Look_at_which_opponent's_hand?", $eligible);
};

$customDQHandlers["ASH_220#LOOK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $cards = SWULookAtOpponentHand(intval($player), null, $opp);
    if (empty($cards)) return;
    SWUQueueMayChooseTarget(intval($player), $cards, "Discard_a_card_from_the_opponent's_hand?", "Choose_a_card", "SEC_017#2");
};
