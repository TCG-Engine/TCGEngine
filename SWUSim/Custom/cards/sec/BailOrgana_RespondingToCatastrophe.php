<?php
// SEC_198
// Cost 2 - Bail Organa - Responding to Catastrophe - [Cunning,Heroism] - Power 2 - HP 4
// Text: On Attack: You may discard a card from your hand. If you do, create a Spy token.

// SEC_198 Bail Organa — On Attack: you may discard a card from your hand. If you do, create a Spy.
$onAttackAbilities["SEC_198:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = ZoneSearch("myHand");
    if (empty($hand)) return;
    SWUQueueMayChooseTarget(intval($player), $hand, "Discard_a_card_to_create_a_Spy?", "Choose_a_card_to_discard", "SEC_198#0");
};

$customDQHandlers["SEC_198#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    DoDiscardCard(intval($player), $lastDecision);
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};
