<?php
// SEC_154
// Cost 6 - Inner Rim Coalition - [Aggression,Heroism] - Power 6 - HP 5
// Text: When Defeated: You may ready a unit that costs 5 or less.

// SEC_154 Inner Rim Coalition — When Defeated: you may ready a unit that costs 5 or less.
$whenDefeatedAbilities["SEC_154:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 5) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Ready_a_unit_that_costs_5_or_less?", "Choose_a_unit", "READY_UNIT");
};
