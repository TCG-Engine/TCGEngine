<?php
// TWI_068
// Cost 1 - Foresight - [Vigilance] - Upgrade Power 0 - Upgrade HP 2
// Text: Attached unit gains: "When the regroup phase starts (before drawing cards): Name a card, then look at the top card of your deck. If it's the named card, you may reveal and draw it."

// TWI_068 Foresight — named a card at regroup start; look at the top of the deck. If it matches, offer
// to reveal and draw it (the peek is private; a taken draw is logged as a public reveal).
$customDQHandlers["TWI_068#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $named = trim($lastDecision);
    $deck  = GetDeck(intval($player));
    if (empty($deck)) return;
    $topID = $deck[0]->CardID ?? '';
    if (CardTitle($topID) !== $named) return;   // top isn't the named card → nothing (stays hidden)
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, 'Reveal_and_draw_the_named_card?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_068#1', 1);
};

$customDQHandlers["TWI_068#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;    // declined the optional draw
    global $playerID; $playerID = intval($player);
    $deck = GetDeck(intval($player));
    $topID = !empty($deck) ? ($deck[0]->CardID ?? '') : '';
    if ($topID !== '') AddGameLogEntry('ABILITY', 'P' . intval($player) . ' revealed and drew ' . GameLogCardRef($topID) . ' (Foresight)', 'ALL');
    DoDrawCard(intval($player), 1);   // draws the top card (the revealed named card)
};
