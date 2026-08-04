<?php
// SOR_146
// Cost 5 - Zeb Orrelios - Headstrong Warrior - [Aggression,Heroism] - Power 5 - HP 5
// Text: When this unit completes an attack: If the defender was defeated, you may deal 4 damage to a ground unit.

// SOR_146 Zeb Orrelios — "When this unit completes an attack: If the defender was defeated, you may
// deal 4 damage to a ground unit." The "if defeated" condition is gated at trigger collection
// (CollectAfterAttackTriggers), so reaching here means the defender died. Any ground unit (friendly
// or enemy, including Zeb himself) is a valid target → one MZMAYCHOOSE → DEAL_UNIT_DAMAGE|4.
$onAttackEndAbilities["SOR_146:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        'Deal_4_damage_to_a_ground_unit', "Deal_4_damage_to_a_ground_unit", 'DEAL_UNIT_DAMAGE|4');
};
