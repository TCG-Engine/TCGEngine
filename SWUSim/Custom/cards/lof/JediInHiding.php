<?php
// LOF_159
// Cost 3 - Jedi In Hiding - [Aggression] - Power 3 - HP 3
// Text: Hidden (This unit can't be attacked if it was played this phase.) / When Defeated: You may use the Force (lose your Force token). If you do, each opponent discards a card from their hand.

// LOF_159 Jedi In Hiding — Hidden + When Defeated: may use the Force → each opponent discards a card.
$whenDefeatedAbilities["LOF_159:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_make_each_opponent_discard?", "LOF_159#0");
};

$customDQHandlers["LOF_159#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    // Twin Suns (Phase 3): each opponent discards (2-player: the one opponent).
    foreach (OpponentsOf(intval($player)) as $opp) {
        SWUDiscardCards(intval($player), 1, $opp);
    }
};
