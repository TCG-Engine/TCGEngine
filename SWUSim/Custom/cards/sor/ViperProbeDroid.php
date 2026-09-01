<?php
// SOR_228  +  SEC_239 (reprint — identical text, one implementation)
// Cost 2 - Viper Probe Droid - [Villainy] - Power 3 - HP 2 - Ground - Imperial, Droid
// Text: When Played: Look at an opponent's hand.
//
// ⚠ BOTH PRINTINGS REGISTER THE SAME CLOSURE, and that is the whole point of this file.
// They used to have SEPARATE implementations in separate files: SOR_228 did the real work here while
// SEC_239's own cards/sec/ViperProbeDroid.php was a stub that wrote a game-log line and nothing else.
// The card's ONLY printed effect is the look, so the reprint did literally nothing a player could see —
// reported as bug #1028, "Viper Probe not doing anything", which was an exact description.
// Reprints consolidate into the earliest printing's file precisely so the two cannot drift apart.
//
// "Look at an opponent's hand" is a real entitlement, not flavour: the opponent's hand is a
// Visibility=Self zone, so it is rendered as card BACKS unless a decision explicitly reveals it. An
// effect that merely logs the look shows the player nothing. SWUQueueShowOpponentHand is the reveal —
// an acknowledge popup of the hand's card images plus an OK button.
$_swuViperProbeLook = function($player, $mzID) {
    // ⚠ "Look at AN OPPONENT'S hand" — an opponent OF YOUR CHOICE. Passing no seat makes
    // SWULookAtOpponentHand fall back to SWUChooseOpponent, which AUTO-PICKS the first live opponent.
    // Filtered to opponents actually HOLDING a card (nothing to look at is a choice among nothing) and
    // auto-resolving at one, so 2-player play is byte-identical. Pattern: SHD_184 Bazine Netal.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;   // nothing to see → no popup, no dangling decision
    SWUQueueChooseOpponent(intval($player), 'SOR_228#LOOK', "Look_at_which_opponent's_hand?", $eligible);
};
$whenPlayedAbilities["SOR_228:0"] = $_swuViperProbeLook;
$whenPlayedAbilities["SEC_239:0"] = $_swuViperProbeLook;

// One handler key for both printings — the queued decision names it, and a single registration cannot
// be silently overwritten by a duplicate the way two same-named keys would be.
$customDQHandlers["SOR_228#LOOK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    SWULookAtOpponentHand(intval($player), null, $opp);   // logs the reveal
    SWUQueueShowOpponentHand(intval($player), $opp);      // present that hand as an acknowledge popup
};
