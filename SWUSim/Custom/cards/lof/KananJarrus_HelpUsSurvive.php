<?php
// LOF_004
// Cost 6 - Kanan Jarrus - Help Us Survive - [Vigilance,Heroism] - Power 3 - HP 6
// Text: Action [1 resource, Exhaust]: Give a Shield token to a Creature or Spectre unit.
// DeployText: Shielded (When you deploy this leader, give a Shield token to him.) / While you control another Creature or Spectre unit, this unit gets +2/+2.
// Epic Action: If you control 6 or more resources, deploy this leader.

// LOF_004 Kanan Jarrus — Action [1 resource, Exhaust]: Give a Shield token to a Creature or Spectre unit.
$leaderAbilities["LOF_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                         ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if (SWUObjGone($o)) continue;
        if (TraitContains($o, 'Creature') || HasTrait($o->CardID ?? '', 'Spectre')) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_Shield_to_a_Creature_or_Spectre_unit", "LOF_004#0");
};

$customDQHandlers["LOF_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        DoGiveShieldToken(intval($player), $lastDecision);
    }
    SWUAfterAction(intval($player));
};
