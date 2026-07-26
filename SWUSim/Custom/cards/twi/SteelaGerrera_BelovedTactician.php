<?php
// TWI_146
// Cost 4 - Steela Gerrera - Beloved Tactician - [Aggression,Heroism] - Power 4 - HP 3
// Text: When Played/When Defeated: You may deal 2 damage to your base. If you do, search the top 8 cards of your deck for a Tactic card, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// TWI_146 Steela Gerrera — "When Played/When Defeated: You may deal 2 damage to your base. If you do,
// search the top 8 cards of your deck for a Tactic card, reveal it, and draw it."
$whenPlayedAbilities["TWI_146:0"] = $whenDefeatedAbilities["TWI_146:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Deal_2_to_your_base_to_search_for_a_Tactic_card?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_146#0", 1);
};

$customDQHandlers["TWI_146#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToBase(2, intval($player), intval($player)); // 2 to your own base
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 8, fn($c) => HasTrait($c, 'Tactic'), 1);
};
