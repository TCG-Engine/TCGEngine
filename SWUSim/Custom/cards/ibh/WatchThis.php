<?php
// IBH_052
// Cost 6 - Watch This - [Cunning]
// Text: Return a non-leader unit that costs 6 or less to its owner's hand. Exhaust each other enemy unit in the same arena.

$whenPlayedAbilities["IBH_052:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
            if (intval(CardCost($o->CardID ?? '')) <= 6) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_(cost_6_or_less)_to_hand", "IBH_052#0");
};

// IBH_052 Watch This — return the chosen unit to its owner's hand, then exhaust each enemy unit in the
// SAME arena (the returned unit is already gone, so "each other" = all remaining enemies there).
$customDQHandlers["IBH_052#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $isSpace = strpos((string)($obj->Location ?? ''), 'Space') !== false;
    SWUBounceUnit(intval($player), $lastDecision);
    $playerID = intval($player);
    $z = $isSpace ? 'theirSpaceArena' : 'theirGroundArena';
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $o->Status = 0;
    }
};
