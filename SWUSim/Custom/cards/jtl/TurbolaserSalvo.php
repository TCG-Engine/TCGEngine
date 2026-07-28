<?php
// JTL_131
// Cost 7 - Turbolaser Salvo - [Command]
// Text: Choose an arena. A friendly space unit deals damage equal to its power to each enemy unit in that arena.

// ── JTL_131 Turbolaser Salvo — arena chosen; pick the friendly space dealer (its power is the AOE). ───
$customDQHandlers["JTL_131#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    $dealers = ZoneSearch('mySpaceArena', AnyUnitFilter);
    if (empty($dealers)) return;
    SWUQueueChooseTarget(intval($player), $dealers,
        "A_friendly_space_unit_deals_its_power_to_each_enemy_in_that_arena", "JTL_131#1|{$arena}");
};

// Dealer chosen → deal its power to each enemy unit in the chosen arena (snapshot UIDs, index-shift safe).
$customDQHandlers["JTL_131#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $arena = (($parts[0] ?? 'GroundArena') === 'SpaceArena') ? 'SpaceArena' : 'GroundArena';
    $dealer = GetZoneObject($lastDecision);
    if (SWUObjGone($dealer)) return;
    $pow = intval(ObjectCurrentPower($dealer));
    if ($pow <= 0) return;
    $uids = [];
    foreach (ZoneSearch('their' . $arena, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, $pow, intval($player));
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_131:0"] = function($player, $mzID = '') {
// Turbolaser Salvo — "Choose an arena. A friendly space unit deals damage equal
                          // to its power to each enemy unit in that arena." Choose arena → choose the
                          // (space) dealer → AOE its power to each enemy in that arena.
            global $playerID;
            $playerID = intval($player);
            if (empty(ZoneSearch('mySpaceArena', AnyUnitFilter))) return; // no friendly space unit → fizzle
            DecisionQueueController::AddDecision($player, 'OPTIONCHOOSE', 'Ground&Space', 1, tooltip: "Choose_an_arena");
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_131#0', 1);
            return;
};
