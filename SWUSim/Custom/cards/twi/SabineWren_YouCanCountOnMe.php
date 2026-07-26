<?php
// TWI_195
// Cost 4 - Sabine Wren - You Can Count On Me - [Cunning,Heroism] - Power 4 - HP 4
// Text: While this unit is exhausted, she can't be attacked (unless she gains Sentinel). / On Attack: You may discard a card from your deck. If it doesn't share an aspect with your base, deal 2 damage to a ground unit.

// TWI_195 Sabine Wren — "On Attack: You may discard a card from your deck. If it doesn't share an
// aspect with your base, deal 2 damage to a ground unit." (The can't-be-attacked-while-exhausted half
// is in SWUGetValidAttackTargets.)
$onAttackAbilities["TWI_195:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (empty(GetDeck(intval($player)))) return; // no card to discard
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        tooltip: 'Discard_top_of_deck_(if_off-aspect_from_your_base,_deal_2_to_a_ground_unit)?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_195#0', 1);
    // Combat owns the after-action.
};

$customDQHandlers["TWI_195#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player)); // discards top of deck, returns the CardID
    if ($milled === null) return;
    $baseArr = GetBase(intval($player));
    $baseCid = (!empty($baseArr) && isset($baseArr[0])) ? ($baseArr[0]->CardID ?? '') : '';
    if (!empty(array_intersect(SWUCardAspectIcons($milled), SWUCardAspectIcons($baseCid)))) return; // shares aspect → no damage
    $targets = array_merge(
        ZoneSearch('myGroundArena', ['Unit', 'Token Unit', 'Leader Unit']),
        ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit'])
    );
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
