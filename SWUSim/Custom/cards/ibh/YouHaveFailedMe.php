<?php
// IBH_095
// Cost 4 - You Have Failed Me - [Aggression,Villainy]
// Text: Defeat a friendly unit. If you do, ready a friendly unit with 5 or less power.

$whenPlayedAbilities["IBH_095:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $friendlies = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    );
    if (empty($friendlies)) return;
    SWUQueueChooseTarget(intval($player), $friendlies, "Defeat_a_friendly_unit", "IBH_095#0");
};

// IBH_095 You Have Failed Me — defeat the chosen friendly; then ready a friendly unit with 5 or less
// power (the defeated one is gone; recompute the candidate list live).
$customDQHandlers["IBH_095#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && ObjectCurrentPower($o) <= 5) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Ready_a_friendly_unit_with_5_or_less_power", "READY_UNIT");
};
