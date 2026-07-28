<?php
// SOR_246
// Cost 3 - You're My Only Hope - [Heroism]
// Text: Look at the top card of your deck. You may play it. It costs [5 resources] less. If your base has 5 or less remaining HP, you may play it for free instead.

// SOR_246 You're My Only Hope — "Play" the top card for 5 less, or free if your base has 5 or less
// remaining HP; "Leave" is a no-op. Event flow's FINISH_PLAY_CARD owns SWUAfterAction, so the play
// must not advance the turn → SWUPlayTopDeckCard (capture/restore turn state around ActivateCard).
$customDQHandlers["SOR_246#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'Play') return;                  // "Leave" → no-op
    global $playerID;
    $playerID = intval($player);
    $bases = GetBase(intval($player));
    $free  = false;
    if (!empty($bases)) {
        $remaining = intval(CardHp($bases[0]->CardID)) - intval($bases[0]->Damage);
        if ($remaining <= 5) $free = true;
    }
    SWUPlayTopDeckCard(intval($player), $free, $free ? 0 : 5);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_246:0"] = function($player, $mzID = '') {
// You're My Only Hope — "Look at the top card of your deck. You may play it.
            // It costs [5 resources] less. If your base has 5 or less remaining HP, you may play it
            // for free instead." The cost mode (−5 vs free) is decided by base HP in the handler.
            global $playerID;
            $playerID = intval($player);
            $idx = _SWUTopDeckFrontIdx(intval($player));
            if ($idx === -1) return;                       // empty deck → nothing to look at
            $topObj = GetDeck(intval($player))[$idx];
            $topID  = $topObj->CardID;
            // Free if the base has ≤5 remaining HP (always playable); otherwise the −5 discount must be
            // affordable. If neither holds, "Play" is impossible and "Leave" is the only outcome, so skip
            // the prompt entirely (the top card just stays put) rather than offer an unplayable "Play".
            $bases = GetBase(intval($player));
            $free  = !empty($bases)
                     && (intval(CardHp($bases[0]->CardID)) - intval($bases[0]->Damage)) <= 5;
            $canPlay = $free
                       || max(0, SWUComputePlayCost(intval($player), $topObj) - 5)
                          <= SWUResourceCount(intval($player), readyOnly: true);
            if (!$canPlay) return;
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Play&Leave", 1, "Play_the_top_card_(costs_5_less,_or_free_if_your_base_has_5_or_less_HP)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_246#0", 1);
            return;
};
