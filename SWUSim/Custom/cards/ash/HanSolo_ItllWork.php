<?php
// ASH_158
// Cost 4 - Han Solo - It'll Work - [Aggression,Heroism] - Power 3 - HP 7
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played: Deal 3 damage to this unit. Give 3 Advantage tokens to a unit.

// ASH_158 Han Solo — Saboteur + When Played: deal 3 damage to this unit; give 3 Advantage tokens to a unit.
$whenPlayedAbilities["ASH_158:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToUnit($mzID, 3, intval($player));   // 3 to this unit
    $tg = SWUAllUnits();
    if (!empty($tg)) SWUQueueChooseTarget(intval($player), $tg, "Give_3_Advantage_tokens_to_a_unit", "GIVE_ADVANTAGE|3");
};
