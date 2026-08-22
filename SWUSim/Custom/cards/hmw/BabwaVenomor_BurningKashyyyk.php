<?php
// HMW_152
// Cost 2 - Babwa Venomor - Burning Kashyyyk - [Aggression][Villainy] - Unit (Ground) 4/4
// Traits: Imperial - Unique
// Text: Overwhelm
//       When Played: An opponent creates a Beast token.
//
// OVERWHELM needs no code: HMW_152 is already in $Overwhelm_Cards (the generator derives membership
// from the card text) and the keyword has generic behaviour tests under Tests/Cases/keywords/. The
// section in this card's own test file pins the MEMBERSHIP, which is a literal and can be wrong.
//
// THE WHOLE CARD IS A DRAWBACK on an efficient body — a 2-cost 4/4 with Overwhelm that hands the other
// side a free 3/3 Creature. So the one thing that must not drift is WHO ends up with the token: an
// implementation that created it for the CASTER is the exact inverse of the card and would still look
// right in a screenshot.
//
// "An OPPONENT CREATES a Beast token" — two words, both load-bearing:
//   * "AN opponent" is a CHOICE, made by Babwa's controller, and only a real choice with 2+ opponents.
//     SWUQueueChooseOpponent already collapses the 2-player case to a PASSPARAMETER that auto-resolves
//     (no prompt), so this could have been written without the seat-count branch — but the inline
//     2-player path is kept so the overwhelmingly common case creates the token WITHOUT a queue
//     round-trip, matching the JTL_155 They Hate That Ship sibling ("an opponent creates 2 TIE
//     Fighters"), which is the same shape end to end.
//   * "CREATES" makes the opponent the CREATOR, not merely the recipient. Passing their seat to
//     SWUCreateUnitToken puts the whole creation under THEIR frame, so their "each token unit you
//     create enters play ready" auras (TWI_203 Chancellor Palpatine, HMW_234 Ritual Dragon) and their
//     ASH_094 Moff Jerjerrod doubling replacement all apply — off their board, never the caster's.
//     Pinned by BeastIsCreatedInTHEIRFrame_TheirEntersReadyAuraApplies, which is the only section that
//     separates "created under them" from "created under me and handed over".
// Mandatory: the text says "creates", not "may create", so there is no decline on either half.

// Twin Suns continuation: the picked seat arrives as "P{n}" in $lastDecision. Nothing else needs to
// ride the Param — the token is created for the PICKED seat, and Babwa's own identity is irrelevant
// by this point (a Babwa that left play between the play and the answer still gave the token away).
$customDQHandlers["HMW_152#OPP"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;                      // unparseable/blank pick — no token rather than a guess
    SWUCreateUnitToken($opp, 'HMW_T03');        // Beast — a 3/3 Ground Creature token
};

$whenPlayedAbilities["HMW_152:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    if (SeatCountForGame() > 2) {
        SWUQueueChooseOpponent(intval($player), "HMW_152#OPP", "Which_opponent_creates_a_Beast?");
        return;
    }
    SWUCreateUnitToken(OtherPlayer(intval($player)), 'HMW_T03');
};
