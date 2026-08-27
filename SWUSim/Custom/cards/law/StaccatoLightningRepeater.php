<?php
// LAW_187
// Cost 3 - "Staccato Lightning" Repeater - [Aggression,Heroism] - Upgrade Power 3 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / When Played: Deal 1 damage to each of up to 3 different ground units.

// LAW_187 "Staccato Lightning" Repeater — When Played (as an upgrade): deal 1 damage to each of up to 3
// different GROUND units (either player's).
$whenPlayedAbilities["LAW_187:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "theirGroundArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    $max = min(3, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $targets), 1,
        tooltip: "Deal_1_damage_to_each_of_up_to_3_ground_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_187#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["LAW_187#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    // Resolve the chosen targets to UniqueIDs FIRST — a lethal hit reindexes the arena, so dealing by
    // the original mzID strings would mis-target the later picks.
    $uids = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        if ($uid <= 0) continue;
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
