<?php
// LOF_148
// Cost 5 - Rey - With Palpatine's Power - [Aggression,Heroism] - Power 5 - HP 5
// Text: When you draw this card during the action phase: If you control a Aggression leader or base, you may reveal this card from your hand. If you do, deal 2 damage to a unit and 2 damage to a base.

// LOF_148 — revealed on draw (see _SWUOnDrawLof148): deal 2 to a unit, then 2 to a base. Both mandatory
// once revealed (the unit step is skipped only if no units are in play). Mirrors JTL_010#0's unit→base chain.
$customDQHandlers["LOF_148#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    // SEC_016 Padmé — revealing LOF_148 from hand is a hand reveal (no-op when no Padmé in play).
    if (function_exists('_SWUSec016React')) _SWUSec016React(intval($player));
    $units = array_values(SWUAllUnits());
    if (!empty($units)) {
        SWUQueueChooseTarget(intval($player), $units, "Deal_2_damage_to_a_unit", "LOF_148#1");
    } else {
        SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_damage_to_a_base", "DEAL_BASE_DAMAGE|2");
    }
};

$customDQHandlers["LOF_148#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== '' && $lastDecision !== 'PASS') {
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_damage_to_a_base", "DEAL_BASE_DAMAGE|2");
};
