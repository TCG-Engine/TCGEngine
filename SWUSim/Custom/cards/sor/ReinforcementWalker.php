<?php
// SOR_119
// Cost 8 - Reinforcement Walker - [Command] - Power 6 - HP 9
// Text: When Played/On Attack: Look at the top card of your deck. Either draw that card or discard it and heal 3 damage from your base.

// SOR_119 Reinforcement Walker — "When Played/On Attack: Look at the top card of your deck.
//   Either draw that card or discard it and heal 3 damage from your base."
// Mandatory either/or → OPTIONCHOOSE (no decline). Fizzles with no decision on an empty deck.
$whenPlayedAbilities["SOR_119:0"] =
$onAttackAbilities["SOR_119:0"]   = function($player, $mzID) {
    $topIdx = _SWUTopDeckFrontIdx(intval($player));
    if ($topIdx === -1) return;                                // no top card to look at
    $topID  = GetDeck(intval($player))[$topIdx]->CardID;       // shown to the acting player only
    // Single-word option labels — OPTIONCHOOSE params are space-delimited in storage; the tooltip
    // carries the full meaning. Leading "@CardID" shows the card being looked at.
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Draw&Discard", 1, "Draw_the_top_card,_or_discard_it_and_heal_3_from_your_base");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_119#0", 1);
};

// Receives the OPTIONCHOOSE label. Both branches act on the top (front) card just looked at.
$customDQHandlers["SOR_119#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision === 'Draw') {
        SWUDrawTopCardFront(intval($player));
    } else {
        // "Discard and heal 3"
        SWUMillTopCard(intval($player));
        OnHealBase(intval($player), intval($player), 3);
    }
};
