<?php
// IBH_011
// Cost 2 - R2-D2 - Known to Make Mistakes - [Cunning,Heroism] - Power 1 - HP 4
// Text: On Attack: If you control a Command unit, exhaust an enemy ground unit that costs 4 or less.

// IBH_011 / IBH_049 R2-D2 — On Attack: if you control a Command unit, exhaust an enemy ground unit that
// costs 4 or less. (MZMAYCHOOSE — the OnAttack-safe choose; a mandatory multi-target MZCHOOSE is dropped.)
$onAttackAbilities["IBH_011:0"] =
$onAttackAbilities["IBH_049:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!_SWUControlsUnitWithAspect(intval($player), 'Command')) return;
    $targets = [];
    foreach (ZoneSearch("theirGroundArena", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 4) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Exhaust_an_enemy_ground_unit_(cost_4_or_less)?",
        "Choose_an_enemy_ground_unit", "EXHAUST_UNIT");
};
