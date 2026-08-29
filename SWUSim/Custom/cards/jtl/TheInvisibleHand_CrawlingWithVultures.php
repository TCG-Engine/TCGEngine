<?php
// JTL_089
// Cost 6 - The Invisible Hand - Crawling With Vultures - [Command,Villainy] - Power 6 - HP 6
// Text: When Played/When this unit completes an attack (and survives): You may search the top 8 cards of your deck for a Droid unit, reveal it, and draw it. If it costs 2 or less, you may play it for free. (Put the other cards on the bottom of your deck in a random order.)

// ── JTL_089 The Invisible Hand — "When Played / When this unit completes an attack (and survives):
// search the top 8 cards of your deck for a Droid unit, reveal it, and draw it. If it costs 2 or less,
// you may play it for free." Uses a JTL_089-specific finalize so the drawn Droid can route into the
// optional free-play rider. The complete-attack copy reuses the identical closure (the "(and survives)"
// gate is CollectAfterAttackTriggers' surviving-attacker null-check). ───────────────────────────────
$whenPlayedAbilities["JTL_089:0"] =
$onAttackEndAbilities["JTL_089:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    _topDeckSearchBegin(intval($player), 8,
        fn($c) => stripos(CardType($c) ?? '', 'Unit') !== false && HasTrait($c, 'Droid'),
        "count:1", "JTL_089#0");
};

// Draw the chosen Droid to hand (like TOPDECKSEARCH_FINALIZE), put the rest on the deck bottom, then —
// if the drawn Droid costs 2 or less — offer to play it for free.
$customDQHandlers["JTL_089#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $freeMz   = null;  // hand mzID of a drawn Droid eligible for the free-play rider
    foreach ($resolved['drawn'] as $cardID) {
        $handObj = AddHand(intval($player), CardID: $cardID);
        if ($handObj !== null && intval(CardCost($cardID)) <= 2) {
            $freeMz = 'myHand-' . intval($handObj->mzIndex);  // count:1 → at most one such card
        }
    }
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if ($freeMz !== null) {
        DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Play_the_drawn_Droid_for_free?");
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_089#1|' . $freeMz, 1);
    }
};

// Play the just-drawn Droid from hand for free (WhenPlayed triggers fire). Mirror SWUPlayTopDeckCard's
// turn-state guard so the nested ActivateCard doesn't double-advance JTL_089's own play.
$customDQHandlers["JTL_089#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID, $gTurnPlayer;
    $playerID = intval($player);
    $handMz = $parts[0] ?? '';
    $o = ($handMz !== '') ? GetZoneObject($handMz) : null;
    if (SWUObjGone($o)) return;
    SWUNestedPlay(intval($player), $handMz, true, 0);  // free play from hand
};
