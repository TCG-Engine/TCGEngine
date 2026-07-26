<?php
// SEC_180
// Cost 3 - Let's Call It War - [Aggression]
// Text: Deal 3 damage to a unit. Then, if you have the initiative, you may deal 2 damage to another unit in the same arena.

$customDQHandlers["SEC_180#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $first = GetZoneObject($lastDecision);
    $firstUID = ($first !== null) ? intval($first->UniqueID ?? 0) : 0;
    $isSpace = ($first !== null) && strpos((string)($first->Location ?? ''), 'Space') !== false;
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
    if (!PlayerHasIniative(intval($player))) return;
    $zones = $isSpace ? ['mySpaceArena', 'theirSpaceArena'] : ['myGroundArena', 'theirGroundArena'];
    $targets = [];
    foreach ($zones as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $firstUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_2_to_another_unit_in_the_same_arena?", "Choose_a_unit", "DEAL_UNIT_DAMAGE|2");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_180:0"] = function($player, $mzID = '') {
// Let's Call It War — "Deal 3 to a unit. Then, if you have the initiative, you
                          // may deal 2 to another unit in the same arena."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Deal_3_to_a_unit", "SEC_180#0");
            return;
};
