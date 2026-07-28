<?php
// ASH_038
// Cost 8 - Purrgil Ultra - [Command,Cunning] - Power 6 - HP 10
// Text: When Played/When Defeated: You may return another friendly non-leader unit to its owner's hand. If you do, deal damage to a unit equal to the returned unit's cost.

// ASH_038 Purrgil Ultra — When Played/When Defeated: you may return ANOTHER friendly non-leader unit to
// its owner's hand. If you do, deal damage to a unit equal to the returned unit's cost.
$whenPlayedAbilities["ASH_038:0"] =
$whenDefeatedAbilities["ASH_038:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'ASH_038#0', 'may' => true,
        'side' => 'my', 'nonLeader' => true, 'excludeSelf' => true,
        'question' => "Return_another_friendly_unit_to_hand_(then_deal_its_cost)?", 'prompt' => "Choose_a_unit_to_return",
    ]);
};

$customDQHandlers["ASH_038#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cost = intval(CardCost($o->CardID ?? ''));
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;   // couldn't return → no damage
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $cost,
        'prompt' => "Deal_{$cost}_damage_to_a_unit",
    ]);
};
