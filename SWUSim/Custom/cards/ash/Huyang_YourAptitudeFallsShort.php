<?php
// ASH_056
// Cost 2 - Huyang - Your Aptitude Falls Short - [Vigilance,Heroism] - Power 2 - HP 4
// Text: On Attack: You may give an upgraded unit -4/-0 for this phase.

// ASH_056 Huyang — On Attack: you may give an upgraded unit -4/-0 for this phase.
$onAttackAbilities["ASH_056:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && _SWUIsUpgraded($o)) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Give_an_upgraded_unit_-4/-0_this_phase?", "Choose_an_upgraded_unit", "APPLY_PHASE_DEBUFF|4|0|ASH_056");
};
