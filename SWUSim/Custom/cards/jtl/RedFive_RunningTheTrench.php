<?php
// JTL_151
// Cost 3 - Red Five - Running the Trench - [Aggression,Heroism] - Power 3 - HP 4
// Text: On Attack: You may deal 2 damage to a damaged unit.

// ── JTL_151 Red Five — On Attack: You may deal 2 damage to a DAMAGED unit. ───────────────────────────
$onAttackAbilities["JTL_151:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_2_to_a_damaged_unit", "Deal_2_to_a_damaged_unit", "DEAL_UNIT_DAMAGE|2");
};
