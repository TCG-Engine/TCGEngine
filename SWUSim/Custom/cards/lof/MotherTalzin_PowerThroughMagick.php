<?php
// LOF_002
// Cost 5 - Mother Talzin - Power Through Magick - [Vigilance,Villainy] - Power 3 - HP 7
// Text: Action [Exhaust, use the Force (lose your Force token)]: Give a unit -1/-1 for this phase.
// DeployText: On Attack: You may give a unit -1/-1 for this phase.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LOF_002 Mother Talzin — On Attack: You may give a unit -1/-1 for this phase.
$onAttackAbilities["LOF_002:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_unit_-1/-1_this_phase?", "Choose_a_unit", "APPLY_PHASE_DEBUFF|1|1|LOF_002");
};

// LOF_002 Mother Talzin — Action [Exhaust, use the Force]: Give a unit -1/-1 for this phase.
$leaderAbilities["LOF_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player); // affordability already confirmed the Force token
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_unit_-1/-1_this_phase", "LOF_002#0");
};

$customDQHandlers["LOF_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUApplyPhaseDebuff($lastDecision, 1, 1, 'LOF_002');
    }
    SWUAfterAction(intval($player));
};
