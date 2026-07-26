<?php
// ASH_013
// Cost 5 - Ezra Bridger - It's Now or Never - [Aggression,Heroism] - Power 3 - HP 6
// Text: When a friendly unit's attack ends: If it dealt 3 or more combat damage to a base, you may exhaust this leader. If you do, give an Advantage token to a different unit.
// DeployText: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When a friendly unit's attack ends: If it dealt 3 or more combat damage to a base, you may give an Advantage token to a different unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

$customDQHandlers["ASH_013#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_013' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $attMz  = $parts[0] ?? '';
    $attObj = ($attMz && str_contains($attMz, '-')) ? GetZoneObject($attMz) : null;
    $attUID = SWUObjUID($attObj);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $attUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Advantage_token_to_a_different_unit", "GIVE_ADVANTAGE|1");
};
