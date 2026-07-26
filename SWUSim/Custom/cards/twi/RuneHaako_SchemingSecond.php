<?php
// TWI_031
// Cost 2 - Rune Haako - Scheming Second - [Vigilance,Villainy] - Power 3 - HP 2
// Text: When Played: If a friendly unit was defeated this phase, you may give a unit -1/-1 for this phase.

// TWI_031 Rune Haako — "When Played: If a friendly unit was defeated this phase, you may give a unit
// -1/-1 for this phase."
$whenPlayedAbilities["TWI_031:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') <= 0) return;
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_unit_-1/-1_this_phase?", "Choose_a_unit", "APPLY_PHASE_DEBUFF|1|1|TWI_031");
};
