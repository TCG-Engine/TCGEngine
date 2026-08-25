<?php
// ASH_258
// Cost 4 - Grassroots Resistance - [Heroism]
// Text: Deal 3 damage to a unit. Heal 3 damage from your base.

$whenPlayedAbilities["ASH_258:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 3);   // heal happens regardless of a damage target
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_3_damage_to_a_unit", "ASH_258#0");
};
