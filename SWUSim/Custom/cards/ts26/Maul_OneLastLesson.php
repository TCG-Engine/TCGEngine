<?php
// TS26_30
// Cost 4 - Maul - One Last Lesson - [Aggression,Cunning] - Power 5 - HP 4
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / When Played: You may attack with another unit.

// TS26_30 Maul (unit) — Sentinel. When Played: you may attack with another unit.
$whenPlayedAbilities["TS26_30:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1
                && intval($o->UniqueID ?? -2) !== $selfUID) $ready[] = $mz;
        }
    }
    if (empty($ready)) return;
    SWUQueueMayChooseTarget(intval($player), $ready, "Attack_with_another_unit?", "Choose_a_unit_to_attack_with", "TS26_30#0");
};

$customDQHandlers["TS26_30#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    BeginSWUAttack(intval($player), $lastDecision);   // combat owns the after-action
};
