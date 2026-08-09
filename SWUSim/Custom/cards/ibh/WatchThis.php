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

// IBH_052 Watch This — return the chosen unit to its owner's hand, then exhaust each OTHER enemy unit in
// the SAME arena.
// ⚠ "each other" must be enforced by IDENTITY, not by absence. The old code relied on the chosen unit
// having left play ("it's already gone, so everything left is an 'other'") — but the return can be
// REFUSED: JTL_103 Chewbacca and friends "can't be defeated or returned to hand by enemy card abilities".
// When the bounce is blocked the chosen unit is still standing there, and an absence-based exclusion
// swept it up and exhausted the very unit that was protected. Snapshot its UniqueID before the bounce and
// skip that UID in the loop; the two halves of the ability are independent, so the exhaust still applies
// to everyone else whether or not the return succeeded.
$customDQHandlers["IBH_052#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $isSpace   = strpos((string)($obj->Location ?? ''), 'Space') !== false;
    $chosenUID = intval($obj->UniqueID ?? 0);
    SWUBounceUnit(intval($player), $lastDecision);
    $playerID = intval($player);
    $z = $isSpace ? 'theirSpaceArena' : 'theirGroundArena';
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if ($chosenUID > 0 && intval($o->UniqueID ?? 0) === $chosenUID) continue;   // "each OTHER"
        $o->Status = 0;
    }
};
