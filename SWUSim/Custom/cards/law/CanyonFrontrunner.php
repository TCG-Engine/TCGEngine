<?php
// LAW_228
// Cost 2 - Canyon Frontrunner - [Cunning] - Power 3 - HP 2
// Text: On Attack: If no other units have attacked this phase (including enemy units), you may give a unit -2/-0 for this phase.

// LAW_228 Canyon Frontrunner — On Attack: if no other units have attacked this phase (incl. enemy
// units), you may give a unit -2/-0 for this phase.
$onAttackAbilities["LAW_228:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $attackers = 0;
    foreach ([1, 2] as $p) {
        foreach (GetGlobalEffects($p) as $ge) {
            if (preg_match('/^SWU_ATTACKED_\d+$/', (string)($ge->CardID ?? ''))) $attackers++;
        }
    }
    if ($attackers > 1) return;   // another unit has already attacked this phase
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Give_a_unit_-2/-0_for_this_phase?", "Choose_a_unit", "APPLY_PHASE_DEBUFF|2|0|LAW_228");
};
