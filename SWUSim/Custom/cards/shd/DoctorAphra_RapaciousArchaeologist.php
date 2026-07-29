<?php
// SHD_015
// Cost 5 - Doctor Aphra - Rapacious Archaeologist - [Cunning,Villainy] - Power 2 - HP 5
// Text: When the regroup phase starts: Discard a card from your deck.
// DeployText: While there are 5 or more different costs among cards in your discard pile, this unit gets +3/+0. / When Deployed: Choose 3 cards in your discard pile with different names. If you do, return 1 of them at random to your hand.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ─── SHD_015 Doctor Aphra (deployed / When Deployed) ───────────────────────────
// "Choose 3 cards in your discard pile with different names. If you do, return 1 of them at random to
// your hand." Offer ONE representative discard mzID per distinct name, so any 3 chosen are automatically
// different-named; require exactly 3 (fizzle if fewer distinct names exist). The random return picks 1 of
// the 3 chosen. (Front-side undeployed regroup-mill + deployed +3/+0 passive live in GameLogic.php.)
$whenPlayedAbilities["SHD_015:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $seen = []; $specs = [];
    foreach (ZoneSearch("myDiscard", null) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $name = SWUObjectTitle($o);
        if ($name === '' || isset($seen[$name])) continue;
        $seen[$name] = true; $specs[] = $mz;
    }
    if (count($specs) < 3) return;                     // can't choose 3 different names → "If you do" fails
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "3|3|" . implode('&', $specs), 1,
        tooltip:"Choose_3_discard_cards_with_different_names");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_015#0", 1);
};

$customDQHandlers["SHD_015#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $picked = ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && $lastDecision !== '')
        ? explode('&', $lastDecision) : [];
    if (count($picked) < 3) return;                    // must choose 3
    SWUReturnFromDiscardToHand(intval($player), $picked[array_rand($picked)]); // return 1 of the 3 at random
};
