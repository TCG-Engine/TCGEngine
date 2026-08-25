<?php
// SOR_158
// Cost 2 - Jedha Agitator - [Aggression] - Power 2 - HP 1
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / On Attack: If you control a leader unit, deal 2 damage to a ground unit or a base.

// SOR_158 Jedha Agitator — On Attack: "If you control a leader unit, deal 2 damage to a ground unit
// or a base." (Saboteur is auto-wired.) Mandatory choose among all ground units + both bases.
$onAttackAbilities["SOR_158:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;
    $targets = array_merge(
        SWUAllUnits(null, 'Ground'),   // unqualified ground pool -> both teams
        SWUAllBaseMzIDs(intval($player), 'any')
    );
    SWUQueueChooseTarget(intval($player), $targets,
        "Deal_2_damage_to_a_ground_unit_or_a_base", "SOR_158#0");
};

$customDQHandlers["SOR_158#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    if (strpos($lastDecision, 'Base') !== false) {
        $tp = SWUMzOwner((string)$lastDecision, intval($player));   // Twin Suns: base owner from the mzID
        SWUDealDamageToBase(2, $tp);
    } else {
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
};
