<?php
// LAW_074
// Cost 5 - Maz Kanata - Where's My Boyfriend? - [Command,Cunning] - Power 4 - HP 4
// Text: When Attack Ends: If this unit survived, search the top 5 cards of your deck for an Underworld unit and play it. It costs 4 resources less and enters play ready. At the start of the regroup phase, put that unit on the bottom of your deck (if it is still in play).

// LAW_074 Maz Kanata — When Attack Ends (if this unit survived — the OnAttackEnd seam only fires for a
// surviving attacker): search the top 5 for an Underworld unit and play it (costs 4 less, enters ready).
// At the start of the regroup phase, put that unit on the bottom of the deck (handled in RegroupPhaseStart
// via the SWU_LAW074_BOTTOM marker).
$onAttackEndAbilities["LAW_074:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Only offer Underworld units the player can actually pay for at the −4 price — otherwise the UI
    // lets you pick an unaffordable unit and the play fizzles at resolve. Mirror the resolve's formula
    // (max(0, SWUComputePlayCost − 4) ≤ ready resources).
    $ready = SWUTotalPaymentCapacity(intval($player));
    _topDeckSearchBegin(intval($player), 5,
        fn($cid) => CardType($cid) === 'Unit' && HasTrait($cid, 'Underworld')
                    && max(0, SWUComputePlayCost(intval($player), (object)['CardID' => $cid]) - 4) <= $ready,
        "count:1", "LAW_074#0");
};

$customDQHandlers["LAW_074#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gForceEnterReady, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $chosen   = $resolved['drawn'];   // up to 1 Underworld unit
    // SWU search convention: the cards you didn't take go to the bottom (shuffled).
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if (empty($chosen)) return;       // declined / none found
    $cardID = $chosen[0];
    // Put the chosen card on top of the deck so SWUPlayTopDeckCard plays IT.
    $deck = &GetDeck(intval($player));
    $topObj = new Deck($cardID, 'Deck', intval($player));
    array_unshift($deck, $topObj);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    // Affordability after the -4 discount; if the player can't pay, it isn't played → send it to the bottom.
    $eff = max(0, SWUComputePlayCost(intval($player), $topObj) - 4);
    if (SWUTotalPaymentCapacity(intval($player)) < $eff) {
        $deck[0]->removed = true;
        DecisionQueueController::CleanupRemovedCards();
        _topDeckPutRemainingToBottom(intval($player), [$cardID]);
        return;
    }
    // Play it: -4 cost, enters ready, fires WhenPlayed, and marked to return to the deck bottom at regroup.
    // asUnitOnly: the ability searched for an Underworld UNIT, so a Piloting card found this way is played
    // as a unit — no Unit-vs-Pilot choice. Applied for parity with the rest of the search-and-play family
    // (SOR_104, LAW_063, LOF_100), where upstream asserts it directly.
    $gForceEnterReady     = true;
    $gPlayGrantTurnEffect = 'SWU_LAW074_BOTTOM';
    SWUPlayTopDeckCard(intval($player), false, 4, true);
    $gForceEnterReady     = null;
    $gPlayGrantTurnEffect = null;
};
