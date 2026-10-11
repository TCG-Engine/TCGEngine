<?php
// HMW_241 Howl — Event, cost 6, [Cunning], Innate.
// "Create a Beast token. You may return a non-leader unit to its owner's hand."
// Independent clauses. Either side; the new Beast is a legal pick (a token ceases instead of going to hand).

$whenPlayedAbilities["HMW_241:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'HMW_T03');
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_241#0", 1);
};

$customDQHandlers["HMW_241#0"] = function($player, $parts, $lastDecision) {
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true, 'may' => true,
        'question' => "Return_a_non-leader_unit_to_its_owners_hand?", 'prompt' => 'Choose_a_unit_to_return',
    ]);
};
