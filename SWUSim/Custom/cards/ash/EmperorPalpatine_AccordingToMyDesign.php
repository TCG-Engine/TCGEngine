<?php
// ASH_015
// Cost 7 - Emperor Palpatine - According to My Design - [Cunning,Villainy] - Power 4 - HP 8
// Text: Action [Exhaust]: Choose an exhausted friendly unit. Give an Advantage token to it for each other friendly unit.
// DeployText: On Attack: You may choose another exhausted friendly unit. If you do, give an Advantage token to it for each other friendly unit.
// Epic Action: If you control 7 or more resources, deploy this leader.

// ASH_015 Emperor Palpatine — may choose another exhausted friendly unit; if you do,
// give it an Advantage token for each OTHER friendly unit.
$onAttackAbilities["ASH_015:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 0
                && intval($o->UniqueID ?? -1) !== $selfUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_Advantage_tokens?",
        "Give_an_Advantage_token_per_other_friendly_unit", "ASH_015#1");
};

$customDQHandlers["ASH_015#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $self    = GetZoneObject($lastDecision);
    $selfUID = SWUObjUID($self);
    $n = 0;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $n++;
        }
    }
    for ($k = 0; $k < $n; $k++) DoGiveAdvantageToken(intval($player), $lastDecision);
};

// ASH_015 Emperor Palpatine — Action [Exhaust]: choose an exhausted friendly unit; give it an Advantage
// token for each OTHER friendly unit.
$leaderAbilities["ASH_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 0) $targets[] = $mz;   // exhausted
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_Advantage_per_other_friendly_unit_to_an_exhausted_unit", "ASH_015#0");
};

$customDQHandlers["ASH_015#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    $self    = GetZoneObject($lastDecision);
    $selfUID = SWUObjUID($self);
    $n = 0;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $n++;
        }
    }
    for ($k = 0; $k < $n; $k++) DoGiveAdvantageToken(intval($player), $lastDecision);
    SWUAfterAction($player);
};
