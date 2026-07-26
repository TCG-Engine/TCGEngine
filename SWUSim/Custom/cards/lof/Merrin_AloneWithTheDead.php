<?php
// LOF_160
// Cost 3 - Merrin - Alone with the Dead - [Aggression] - Power 2 - HP 5
// Text: On Attack: You may discard a card from your hand. If you do, deal 2 damage to a unit.

// LOF_160 Merrin — On Attack: may discard a card from hand. If you do, deal 2 damage to a unit.
$onAttackAbilities["LOF_160:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = ZoneSearch("myHand", null);
    if (empty($hand)) return;
    if (empty(SWUAllUnits())) return; // nothing to damage → skip
    SWUQueueMayChooseTarget(intval($player), $hand, "Discard_a_card_to_deal_2_to_a_unit?", "Choose_a_card_to_discard", "LOF_160#0");
};

$customDQHandlers["LOF_160#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    DoDiscardCard(intval($player), $lastDecision);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_a_unit", "DEAL_UNIT_DAMAGE|2");
};
