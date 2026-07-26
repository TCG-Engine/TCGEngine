<?php
// SOR_142
// Cost 2 - Sabine Wren - Explosives Artist - [Aggression,Heroism] - Power 2 - HP 3
// Text: While there are at least 3 aspects among other friendly units, this unit can't be attacked (unless she gains Sentinel). / On Attack: You may deal 1 damage to the defender or to a base.

// SOR_142 Sabine Wren — On Attack: "You may deal 1 damage to the defender or to a base." Attacking a
// base auto-pings that base (the defender IS a base, no choice); attacking a unit offers a may-choose
// between the defender unit and either base.
$onAttackAbilities["SOR_142:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    if (strpos($defenderMz, 'Base') !== false) {
        // Attacking a base → always ping that base.
        $tp = SWUMzOwner((string)$defenderMz, intval($player));   // Twin Suns: base owner from the mzID
        SWUDealDamageToBase(1, $tp);
        return;
    }
    // Attacking a unit → may deal 1 to the defender or a base.
    $targets = [$defenderMz, 'theirBase-0', 'myBase-0'];
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_1_to_the_defender_or_a_base", "Deal_1_damage_to_the_defender_or_a_base", "SOR_142#0");
};

$customDQHandlers["SOR_142#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    if (strpos($lastDecision, 'Base') !== false) {
        $tp = SWUMzOwner((string)$lastDecision, intval($player));   // Twin Suns: base owner from the mzID
        SWUDealDamageToBase(1, $tp);
    } else {
        SWUDealDamageToUnit($lastDecision, 1, intval($player));
    }
};
