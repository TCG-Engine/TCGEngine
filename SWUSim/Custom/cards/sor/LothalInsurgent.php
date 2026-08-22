<?php
// SOR_190
// Cost 2 - Lothal Insurgent - [Cunning,Heroism] - Power 3 - HP 2
// Text: When Played: If you played another card this phase, each opponent draws a card then discards a random card from their hand.

// SOR_190 Lothal Insurgent — "When Played: If you played another card this phase, each opponent
// draws a card then discards a random card from their hand." The SWU_CARDS_PLAYED counter includes
// Lothal itself, so >1 means another card was played.
$whenPlayedAbilities["SOR_190:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_CARDS_PLAYED') <= 1) return;
    // "EACH opponent" — one draw-then-random-discard per live opponent. The old comment already knew
    // this was the Twin Suns answer and left the 2-seat code in place.
    // ⚠ `continue`, not `return`, when a seat has nothing to discard: an empty hand on one opponent must
    //   not stop the rest of the table resolving.
    foreach (OpponentsOf(intval($player)) as $opp) {
        DoDrawCard($opp, 1);
        $hand = &GetHand($opp);
        $liveIdx = [];
        foreach ($hand as $i => $c) { if (empty($c->removed)) $liveIdx[] = $i; }
        if (empty($liveIdx)) { unset($hand); continue; }
        $pick = $liveIdx[array_rand($liveIdx)];
        $cid  = $hand[$pick]->CardID;
        $hand[$pick]->Remove();
        unset($hand);
        SWUAddToDiscard($opp, $cid, 'HAND');
        DecisionQueueController::CleanupRemovedCards();
        AddGameLogEntry('DISCARD', "P{$opp} drew a card and discarded " . GameLogCardRef($cid) . ' at random');
    }
};
