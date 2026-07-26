<?php
// SOR_173
// Cost 5 - Bombing Run - [Aggression]
// Text: Choose an arena (ground or space). Deal 3 damage to each unit in that arena.

// SOR_173 Bombing Run — deal 3 to each unit in the chosen arena (both players).
// YES = Ground, NO = Space. Dealing damage can defeat units and shift indices, so
// snapshot UniqueIDs first, then re-resolve the current mzID per UID before each hit.
$customDQHandlers["SOR_173#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $arena = ($lastDecision === "Space") ? "SpaceArena" : "GroundArena";
    $uids = [];
    foreach (array_merge(
        ZoneSearch("my$arena",    AnyUnitFilter),
        ZoneSearch("their$arena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
    }
    foreach ($uids as $uid) {
        $found = null;
        foreach (array_merge(
            ZoneSearch("my$arena",    AnyUnitFilter),
            ZoneSearch("their$arena", AnyUnitFilter)
        ) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID) === $uid) { $found = $mz; break; }
        }
        if ($found !== null) SWUDealDamageToUnit($found, 3, intval($player));
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_173:0"] = function($player, $mzID = '') {
// Bombing Run — "Choose an arena. Deal 3 to each unit in that arena."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1, "Choose_an_arena_to_bomb");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_173#0", 1);
            return;
};
