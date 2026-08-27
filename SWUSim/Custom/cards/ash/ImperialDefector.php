<?php
// ASH_250
// Cost 2 - Imperial Defector - [Heroism] - Power 3 - HP 2
// Text: When Played: Look at an opponent's hand.

// ASH_250 Imperial Defector — When Played: look at an opponent's hand. (Information only; the reveal is
// logged for the controller.)
$whenPlayedAbilities["ASH_250:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ "Look at AN OPPONENT'S hand" — an opponent OF YOUR CHOICE. Passing no seat makes
    // SWULookAtOpponentHand fall back to SWUChooseOpponent, which AUTO-PICKS the first live opponent —
    // the sweep's original placeholder. Filtered to opponents actually HOLDING a card (nothing to look at
    // is a choice among nothing) and auto-resolving at one, so Premier is byte-identical. Pattern: SHD_184
    // Bazine Netal, the canonical analogue for this clause.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'ASH_250#LOOK', "Look_at_which_opponent's_hand?", $eligible);
};

$customDQHandlers["ASH_250#LOOK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp > 0) SWULookAtOpponentHand(intval($player), null, $opp);
};
