<?php
// TS26_71
// Cost 3 - Take Action - [Aggression]
// Text: Deal 3 damage to a unit. (Cost reduction via $playCostModifiers["TS26_71"].)

$whenPlayedAbilities["TS26_71:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_3_damage_to_a_unit", "DEAL_UNIT_DAMAGE|3");
};
