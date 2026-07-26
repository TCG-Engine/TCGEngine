<?php
// ASH_236
// Cost 3 - Far Far Away - [Cunning]
// Text: Return a friendly non-leader unit to its owner's hand. If you do, return an enemy non-leader unit to its owner's hand.

// ASH_236 Far Far Away — return the chosen friendly unit; if it returned, return an enemy non-leader unit.
$customDQHandlers["ASH_236#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;   // couldn't return friendly → stop
    $enemy = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $enemy[] = $mz;
        }
    }
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Return_an_enemy_non-leader_unit_to_hand", "BOUNCE_UNIT");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_236:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Return_a_friendly_non-leader_unit_to_hand", "ASH_236#0");
};
