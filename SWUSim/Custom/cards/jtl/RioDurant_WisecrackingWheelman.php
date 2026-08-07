<?php
// JTL_015
// Cost 5 - Rio Durant - Wisecracking Wheelman - [Cunning,Villainy] - Power 3 - HP 5 - Upgrade Power 3 - Upgrade HP 5
// Text: Action [1 resource, Exhaust]: Attack with a space unit. It gets +1/+0 and gains Saboteur for this attack. (Ignore Sentinel and defeat the defender's Shields.)
// DeployText: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / Attached unit is a leader unit. It gains Saboteur. If it's a Transport, it also gets +1/+0. /
// Epic Action: If you control 5 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

$customDQHandlers["JTL_015#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, 'JTL_015');          // Saboteur for this attack (registry, attack duration)
    SWUAddAttackPowerBonus($lastDecision, 1);         // +1/+0 for this attack
    BeginSWUAttack(intval($player), $lastDecision);
};

// JTL_015 Rio Durant — Leader Action [1 resource, Exhaust]: Attack with a space unit. It gets +1/+0
// and gains Saboteur for this attack. Continuation grants the per-attack effects then BeginSWUAttack.
$leaderAbilities["JTL_015"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $attackers = array_values(array_filter(
        ZoneSearch('mySpaceArena', AnyUnitFilter),
        function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }
    ));
    if (empty($attackers)) { SWUAfterAction($player); return; } // no ready space unit → fizzle
    SWUQueueChooseTarget($player, $attackers,
        "Attack_with_a_space_unit_(+1/+0,_Saboteur_this_attack)", "JTL_015#0");
};
