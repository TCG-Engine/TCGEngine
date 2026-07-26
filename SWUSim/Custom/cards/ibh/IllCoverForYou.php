<?php
// IBH_005
// Cost 3 - I'll Cover For You - [Cunning]
// Text: Deal 1 damage to an enemy unit and 1 damage to another enemy unit.

$whenPlayedAbilities["IBH_005:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_to_an_enemy_unit", "IBH_005#0");
};
$whenPlayedAbilities["IBH_039:0"] = $whenPlayedAbilities["IBH_005:0"];

// IBH_005 / IBH_039 I'll Cover For You — first enemy got 1; now deal 1 to ANOTHER enemy unit (exclude
// the first by UID). Both halves mandatory but each fizzles with no valid target.
$customDQHandlers["IBH_005#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $first    = GetZoneObject($lastDecision);
    $firstUID = ($first !== null) ? intval($first->UniqueID ?? 0) : 0;
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $firstUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_to_another_enemy_unit", "DEAL_UNIT_DAMAGE|1");
};
