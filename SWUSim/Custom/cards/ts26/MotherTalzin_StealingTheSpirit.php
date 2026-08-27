<?php
// TS26_26
// Cost 5 - Mother Talzin - Stealing the Spirit - [Command,Cunning,Villainy] - Power 5 - HP 4
// Text: Sentinel / When Defeated: Look at an opponent's hand and discard a card from it. If you do, they draw a card. If the discarded card is a unit, for this phase you may play it from their discard pile, ignoring its aspect penalties.

// TS26_26 Mother Talzin — When Defeated: look at an opponent's hand and discard a card from it; if you
// do, they draw a card. If the discarded card is a unit, for this phase you may play it from their discard
// pile ignoring aspect penalties (the OTPN modifier, cleared at RegroupPhaseStart). The look-hand + discard
// + opponent-draws half mirrors SEC_017#2; the unit-replay half mirrors SEC_205's OTPN stamp.
$whenDefeatedAbilities["TS26_26:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ "Look at AN OPPONENT'S hand" — an opponent OF YOUR CHOICE. Passing no seat makes
    // SWULookAtOpponentHand fall back to SWUChooseOpponent, which AUTO-PICKS the first live opponent —
    // the sweep's original placeholder. Filtered to opponents actually HOLDING a card (nothing to look at
    // is a choice among nothing) and auto-resolving at one, so Premier is byte-identical. Pattern: SHD_184
    // Bazine Netal, the canonical analogue for this clause.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'TS26_26#LOOK', "Look_at_which_opponent's_hand?", $eligible);
};

$customDQHandlers["TS26_26#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || $mz === '-' || $mz === 'PASS') return;   // declined → no discard, no draw
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    // The hand is whichever seat the chosen card came from — the mzID names it. OtherPlayer() sent the
    // discard, the game-log line and the replacement draw to seat 2 regardless of whose hand was read.
    $opp    = SWUMzOwner((string)$mz, intval($player));
    $cardID = $obj->CardID;
    $obj->Remove();
    SWUAddToDiscard($opp, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
    DoDrawCard($opp, 1);   // "If you do, they draw a card."
    // "If the discarded card is a unit, for this phase you may play it from their discard, ignoring aspect penalties."
    if (CardType($cardID) === 'Unit') {
        $discard = &GetDiscard($opp);
        for ($i = count($discard) - 1; $i >= 0; $i--) {
            if (!empty($discard[$i]->removed)) continue;
            if (($discard[$i]->CardID ?? '') === $cardID) { $discard[$i]->Modifier = 'OTPN'; break; }
        }
    }
};

$customDQHandlers["TS26_26#LOOK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $cards = SWULookAtOpponentHand(intval($player), null, $opp);
    if (empty($cards)) return;
    SWUQueueMayChooseTarget(intval($player), $cards, "Discard_a_card_from_the_opponent's_hand?", "Choose_a_card", "TS26_26#0");
};
