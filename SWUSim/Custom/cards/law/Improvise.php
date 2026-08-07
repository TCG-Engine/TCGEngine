<?php
// LAW_242
// Cost 1 - Improvise - [Cunning]
// Text: Look at the top card of your deck. You may play it. It costs 1 resource less. If you don't, you may discard it.

// LAW_242 Improvise — Play the top card (−1), discard it, or leave it.
$customDQHandlers["LAW_242#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'Play')         SWUPlayTopDeckCard(intval($player), false, 1);
    elseif ($lastDecision === 'Discard')  SWUMillTopCard(intval($player));
    // 'Leave' → no-op
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_242:0"] = function($player, $mzID = '') {
// Improvise — "Look at the top card of your deck. You may play it. It costs 1
                          // resource less. If you don't, you may discard it."
            global $playerID; $playerID = intval($player);
            $idx = _SWUTopDeckFrontIdx(intval($player));
            if ($idx === -1) return;
            $topObj = GetDeck(intval($player))[$idx];
            $topID  = $topObj->CardID;
            // Only offer "Play" if the player can afford the top card at its −1 discount — otherwise picking
            // Play just fizzles at resolve. Discard / Leave are always available.
            $canPlay = max(0, SWUComputePlayCost(intval($player), $topObj) - 1)
                       <= SWUTotalPaymentCapacity(intval($player));
            $opts = "@{$topID}" . ($canPlay ? "&Play" : "") . "&Discard&Leave";
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", $opts, 1, "Play_the_top_card_(costs_1_less),_discard_it,_or_leave_it");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_242#0", 1);
            return;
};
