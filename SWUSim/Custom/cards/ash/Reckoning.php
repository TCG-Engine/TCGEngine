<?php
// ASH_187
// Cost 3 - Reckoning - [Aggression]
// Text: Deal damage to a unit equal to the total amount of damage on all units you control.

$whenPlayedAbilities["ASH_187:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $total = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $total += intval($u->Damage ?? 0); }
    if ($total <= 0) return;   // no damage on your units → nothing to deal
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_{$total}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$total}");
};
