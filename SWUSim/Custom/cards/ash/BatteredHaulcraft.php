<?php
// ASH_071
// Cost 2 - Battered Haulcraft - [Vigilance] - Power 2 - HP 3
// Text: When Played: Deal 1 damage to this unit and 1 damage to an enemy space unit.

// ASH_071 Battered Haulcraft — When Played: deal 1 damage to this unit and 1 damage to an enemy space
// unit. (Self-damage is mandatory; the enemy-space hit fizzles cleanly if there's no enemy space unit.)
$whenPlayedAbilities["ASH_071:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToUnit($mzID, 1, intval($player));   // 1 to this unit (mandatory)
    $tg = ZoneSearch("theirSpaceArena", AnyUnitFilter);
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_1_to_an_enemy_space_unit", "DEAL_UNIT_DAMAGE|1");
};
