<?php
// SHD_222
// Cost 1 - Enticing Reward - [Cunning] - Upgrade Power 0 - Upgrade HP 0
// Text: Attached unit gains: "Bounty - Search the top 10 cards of your deck for 2 non-unit cards, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.) Then, if this unit isn't unique, discard a card from your hand."

// ─── SHD_222 Enticing Reward — post-search "discard a card from your hand" ─────
// Queued by the SHD_222 SWUCollectBounty case AFTER DoTopDeckSearch's decisions (same block →
// runs after the search finalize, so the drawn cards are already in hand). Only queued for a
// non-unique host.
$customDQHandlers["SHD_222#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();
    $hand = ZoneSearch('myHand');
    if (empty($hand)) return;
    SWUQueueChooseTarget(intval($player), $hand,
        "Discard_a_card_from_your_hand", "DISCARD_FROM_OWN_HAND|" . intval($player));
};
