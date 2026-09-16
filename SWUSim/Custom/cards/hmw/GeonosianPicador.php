<?php
// HMW_199 Geonosian Picador — Unit (Ground) 2/2, cost 3, [Cunning][Villainy], Separatist.
// "When Played: Create a Beast token. Then, give a Weakness token to a friendly unit."
// Mandatory. The Beast (and the Picador) are friendly units, so the Weakness always has a target — built
// after the Beast exists. "friendly" spans the team.

$whenPlayedAbilities["HMW_199:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'HMW_T03');
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_199#0", 1);
};

$customDQHandlers["HMW_199#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'GIVE_WEAKNESS', 'side' => 'friendly',
        'prompt' => 'Give_a_Weakness_token_to_a_friendly_unit',
    ]);
};
