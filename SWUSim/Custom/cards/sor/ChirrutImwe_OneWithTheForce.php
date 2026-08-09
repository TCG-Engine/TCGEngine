<?php
// SOR_004
// Cost 5 - Chirrut Îmwe - One With The Force - [Vigilance,Heroism] - Power 3 - HP 5
// Text: Action [Exhaust]: Give a unit +0/+2 for this phase.
// DeployText: During the action phase, this unit isn't defeated by having no remaining HP. (During the regroup phase, if he has no remaining HP, defeat him.)
// Epic Action: If you control 5 or more resources, deploy this leader.

// SOR_004 Chirrut Îmwe — Leader Action [Exhaust]: Give a unit +0/+2 for this phase.
// "a unit" = any unit (friendly or enemy). 1 target auto-resolves; the buff flows through
// the existing APPLY_PHASE_BUFF handler (SWUBUFF_0_2, cleared at RegroupPhaseStart).
$leaderAbilities["SOR_004"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, 'Give_a_unit_+0/+2_for_this_phase', 'APPLY_PHASE_BUFF|0|2|SOR_004');
    SWUQueueAfterAction($player);
};
