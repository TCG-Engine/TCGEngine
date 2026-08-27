<?php
// JTL_142
// Cost 6 - Darth Vader - Scourge of Squadrons - [Aggression,Villainy] - Power 7 - HP 7 - Upgrade Power 3 - Upgrade HP 3
// Text: / Piloting [3 resources Aggression Villainy] / Attached unit gains: "On Attack: You may deal 1 damage to a unit. If a unit is defeated this way, you may deal 1 damage to a unit or base."

// JTL_142 Darth Vader (pilot) — granted "On Attack: You may deal 1 damage to a unit. If a unit is
// defeated this way, you may deal 1 damage to a unit or base." (chain target list includes enemy base.)
$onAttackAbilities["JTL_142:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Deal_1_damage_to_a_unit", "Choose_a_unit", "JTL_142#0");
};

$customDQHandlers["JTL_142#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    if (SWUFindMzByUID($uid) !== null) return;   // target survived → no chain
    // A unit was defeated this way → may deal 1 to a unit or base.
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    // ⚠ 'theirBase-0' is a HAND-BUILT relative mzID: it names SEAT 2 and nothing else, so above two seats
    // a far seat's base could not be targeted at all. SWUAllBaseMzIDs(…, 'any') is the caster's own base
    // plus EVERY opponent's, as real p{n}Base mzIDs. (This shape is invisible to a seat-helper scan —
    // there is no OtherPlayer() here, just a string.)
    foreach (SWUAllBaseMzIDs(intval($player), 'any') as $bmz) $targets[] = $bmz;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_unit_or_base", "Choose_a_target", "JTL_142#1");
};

// JTL_142 step 2 — deal 1 to the chosen unit or base (the "if a unit is defeated this way" chain).
$customDQHandlers["JTL_142#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    if (strpos($lastDecision, 'Base') !== false) {
        $dp = SWUMzOwner((string)$lastDecision, intval($player));   // SWUMzOwner reads the seat OUT OF the mzID; the my/their ternary named seat 2 above two seats.
        SWUDealDamageToBase(1, $dp, intval($player));
        return;
    }
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
};
