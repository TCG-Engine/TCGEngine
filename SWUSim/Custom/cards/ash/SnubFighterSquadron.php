<?php
// ASH_194
// Cost 4 - Snub Fighter Squadron - [Cunning,Villainy] - Power 4 - HP 3
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: Deal 1 damage to a space unit.

// ASH_194 Snub Fighter Squadron — Ambush (keyword) + When Played: deal 1 damage to a space unit.
$whenPlayedAbilities["ASH_194:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits(null, SpaceArena);
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_1_to_a_space_unit", "DEAL_UNIT_DAMAGE|1");
};
