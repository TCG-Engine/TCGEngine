<?php
// LOF_003
// Cost 6 - Ahsoka Tano - Fighting For Peace - [Vigilance,Heroism] - Power 5 - HP 6
// Text: Action [Exhaust, use the Force (lose your Force token)]: Give a friendly unit Sentinel for this phase. (Enemy units in its arena must attack a sentinel when they attack you.)
// DeployText: On Attack: You may give a friendly unit Sentinel for this phase.
// Epic Action: If you control 6 or more resources, deploy this leader.

// LOF_003 Ahsoka Tano — On Attack: You may give a friendly unit Sentinel for this phase.
$onAttackAbilities["LOF_003:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = array_values(array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)));
    if (empty($friendly)) return;
    SWUQueueMayChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_Sentinel?", "Choose_a_friendly_unit", "GRANT_PHASE_KEYWORD|SENTINEL^LOF_003");
};

// LOF_003 Ahsoka Tano — Action [Exhaust, use the Force]: Give a friendly unit Sentinel for this phase.
$leaderAbilities["LOF_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_friendly_unit_Sentinel_this_phase", "LOF_003#0");
};

$customDQHandlers["LOF_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        AddTurnEffect($lastDecision, 'SENTINEL^LOF_003');
    }
    SWUAfterAction(intval($player));
};
