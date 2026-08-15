<?php
// SOR_129
// Cost 2 - Admiral Ozzel - Overconfident - [Aggression,Villainy] - Power 2 - HP 3
// Text: Action [exhaust]: Play an Imperial unit from your hand (paying its cost). It enters play ready. Each opponent may ready a unit.

// SOR_129 Admiral Ozzel — Action [Exhaust]: Play an Imperial unit from your hand (paying its cost).
// It enters play ready. Each opponent may ready a unit.
// The empty-pool path may NOT fizzle the whole action: "Each opponent may ready a unit" is
// unconditional (candidate #2 fix, 2026-08-14), so with no playable Imperial the ready offer is
// still queued before the after-action.
$unitAbilities["SOR_129"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUPlayablesAtDiscount($player, 'myHand', ['Unit'], 0, fn($cid) => HasTrait($cid, 'Imperial'));
    if (empty($targets)) {
        DecisionQueueController::AddDecision(OtherPlayer(intval($player)), "CUSTOM", "OZZEL_READY_OFFER", 1);
        SWUAfterAction($player);
        return;
    }
    SWUQueueChooseTarget(intval($player), $targets,
        "Play_an_Imperial_unit_from_your_hand_(it_enters_ready)", 'OZZEL_PLAY', may: true);
};
