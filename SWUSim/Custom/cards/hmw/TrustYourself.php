<?php
// HMW_101 Trust Yourself — Event, cost 2, [Vigilance], Innate.
// "Give a Shield token to a unit. Search the top 3 cards of your deck for a card and draw it."
// Two independent clauses: with no unit in play the Shield is skipped and the search still happens.
// The search is queued BEHIND the Shield pick.

$whenPlayedAbilities["HMW_101:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    GiveTokenUpgrade(intval($player), '', [
        'token' => 'SHIELD', 'friendlyOnly' => false, 'prompt' => 'Give_a_Shield_token_to_a_unit',
    ]);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_101#0", 1);
};

$customDQHandlers["HMW_101#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 3, fn($c) => true, 1, 'cards');
};
