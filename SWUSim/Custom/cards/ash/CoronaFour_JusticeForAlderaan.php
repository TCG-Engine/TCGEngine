<?php
// ASH_043
// Cost 2 - Corona Four - Justice for Alderaan - [Cunning,Vigilance,Heroism] - Power 2 - HP 3
// Text: On Attack: You may give a unit -2/-0 for this phase. / When Defeated: You may defeat a non-leader unit with 0 power.

// ASH_043 Corona Four — On Attack: you may give a unit -2/-0 for this phase. When Defeated: you may defeat
// a non-leader unit with 0 power.
$onAttackAbilities["ASH_043:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Give_a_unit_-2/-0_this_phase?", "Choose_a_unit", "APPLY_PHASE_DEBUFF|2|0|ASH_043");
};

$whenDefeatedAbilities["ASH_043:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !IsLeaderUnit($o) && intval(ObjectCurrentPower($o)) === 0) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Defeat_a_non-leader_unit_with_0_power?", "Choose_a_unit", "DEFEAT_UNIT");
};
