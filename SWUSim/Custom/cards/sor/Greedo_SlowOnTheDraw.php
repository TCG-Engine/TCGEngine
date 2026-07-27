<?php
// SOR_204
// Cost 1 - Greedo - Slow on the Draw - [Cunning] - Power 3 - HP 1
// Text: When Defeated: You may discard a card from your deck. If it's not a unit, deal 2 damage to a ground unit.

// SOR_204 Greedo — "When Defeated: You may discard a card from your deck. If it's not a unit, deal
// 2 damage to a ground unit." Optional → YESNO; the discard is the top of the controller's deck.
$whenDefeatedAbilities["SOR_204:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, 'Discard_the_top_of_your_deck?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SOR_204#0', 1);
};

$customDQHandlers["SOR_204#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;
    if (strpos(CardType($milled) ?? '', 'Unit') !== false) return; // a unit → no damage
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'any', 'arena' => 'Ground',
        'prompt' => 'Deal_2_damage_to_a_ground_unit',
    ]);
};
