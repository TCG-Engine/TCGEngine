<?php
// SOR_238
// Cost 2 - C-3PO - Protocol Droid - [Heroism] - Power 1 - HP 4
// Text: When Played/On Attack: Choose a number, then look at the top card of your deck. If its cost is the chosen number, you may reveal and draw it. (Otherwise, leave it on top of your deck.)

// SOR_238 C-3PO — "When Played/On Attack: Choose a number, then look at the top card of your deck.
//   If its cost is the chosen number, you may reveal and draw it. (Otherwise, leave it on top.)"
// The number is chosen BLIND (before looking), so no card is shown on the NUMBERCHOOSE.
$whenPlayedAbilities["SOR_238:0"] =
$onAttackAbilities["SOR_238:0"]   = function($player, $mzID) {
    if (_SWUTopDeckFrontIdx(intval($player)) === -1) return;   // empty deck → nothing to look at
    DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "0|10", 1, "Choose_a_number_(a_card_cost)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_238#0", 1);
};

// Receives the chosen number; the player ALWAYS gets to look at the top card (the "@CardID" image
// is shown either way). If its cost matches the chosen number, offer reveal-and-draw; otherwise the
// player just acknowledges the peek and it stays on top (you looked, but can't do anything with it).
$customDQHandlers["SOR_238#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $idx = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $topID  = GetDeck(intval($player))[$idx]->CardID;
    $chosen = intval($lastDecision);
    if (intval(CardCost($topID)) === $chosen) {
        DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Draw&Leave", 1, "Cost_matches_-_reveal_and_draw_the_top_card,_or_leave_it_on_top");
    } else {
        // Whiff: still let the player peek the card; the only outcome is to leave it on top.
        DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&OK", 1, "Top_card_cost_does_not_match_-_it_stays_on_top");
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_238#1", 1);
};

// Reveal-and-draw step: "Draw" reveals (public) then draws the top card; "Leave" is a no-op.
$customDQHandlers["SOR_238#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'Draw') return;
    global $playerID;
    $playerID = intval($player);
    $idx = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $topID = GetDeck(intval($player))[$idx]->CardID;
    AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . GameLogCardRef($topID) . ' and drew it');
    SWUDrawTopCardFront(intval($player));
};
