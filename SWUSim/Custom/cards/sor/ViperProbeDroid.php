<?php
// SOR_228
// Cost 2 - Viper Probe Droid - [Villainy] - Power 3 - HP 2
// Text: When Played: Look at an opponent's hand.

// SOR_228 Viper Probe Droid — "When Played: Look at an opponent's hand." (information only)
// Shows the opponent's hand to the player as an acknowledge popup (card images + an OK button).
$whenPlayedAbilities["SOR_228:0"] = function($player, $mzID) {
    // ⚠ "Look at AN OPPONENT'S hand" — an opponent OF YOUR CHOICE. Passing no seat makes
    // SWULookAtOpponentHand fall back to SWUChooseOpponent, which AUTO-PICKS the first live opponent —
    // the sweep's original placeholder. Filtered to opponents actually HOLDING a card (nothing to look at
    // is a choice among nothing) and auto-resolving at one, so Premier is byte-identical. Pattern: SHD_184
    // Bazine Netal, the canonical analogue for this clause.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'SOR_228#LOOK', "Look_at_which_opponent's_hand?", $eligible);
};

$customDQHandlers["SOR_228#LOOK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    SWULookAtOpponentHand(intval($player), null, $opp);   // logs the reveal
    SWUQueueShowOpponentHand(intval($player), $opp);      // present that hand as an acknowledge popup
};
