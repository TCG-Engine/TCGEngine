<?php
// SOR_192
// Cost 3 - Ezra Bridger - Resourceful Troublemaker - [Cunning,Heroism] - Power 3 - HP 4
// Text: When this unit completes an attack: Look at the top card of your deck. You may play it, discard it, or leave it on top of your deck.

// SOR_192 Ezra Bridger — "When this unit completes an attack: Look at the top card of your deck.
//   You may play it, discard it, or leave it on top of your deck."
// First consumer of the On Attack End trigger (generator now maps "completes an attack:" → onAttackEnd).
// Three named choices → OPTIONCHOOSE; fizzles with no decision on an empty deck.
$onAttackEndAbilities["SOR_192:0"] = function($player, $mzID) {
    $topIdx = _SWUTopDeckFrontIdx(intval($player));
    if ($topIdx === -1) return;                                // no top card to look at
    $topID  = GetDeck(intval($player))[$topIdx]->CardID;       // shown to the acting player only
    // Single-word option labels (OPTIONCHOOSE params are space-delimited in storage); "Leave" =
    // leave it on top. Leading "@CardID" shows the card being looked at; tooltip carries meaning.
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Play&Discard&Leave", 1, "Play_the_top_card,_discard_it,_or_leave_it_on_top");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_192#0", 1);
};

$customDQHandlers["SOR_192#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision === 'Discard') {
        SWUMillTopCard(intval($player));
    } elseif ($lastDecision === 'Play') {
        // Play the top card paying its normal cost. SWUPlayTopDeckCard does NOT advance the turn —
        // the parent attack action (combat) owns SWUAfterAction. Unaffordable → no-op (stays on top).
        SWUPlayTopDeckCard(intval($player));
    }
    // "Leave it on top" → no-op.
};
