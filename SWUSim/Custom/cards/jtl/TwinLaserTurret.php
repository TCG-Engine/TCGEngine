<?php
// JTL_172
// Cost 2 - Twin Laser Turret - [Aggression] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a Vehicle unit. / Attached unit gains: "On Attack: Deal 1 damage to each of up to 2 units in this arena."

// ── JTL_172 Twin Laser Turret — On Attack (granted via upgrade) ──────────────
// Attached unit gains: "On Attack: Deal 1 damage to each of up to 2 units in
// this arena." $mzID is the host unit's arena mzID. $playerID is $player.
// "Up to 2" = an MZMULTICHOOSE (min 0) over the units in the host's arena (both
// players), then deal 1 to each pick (JTL_172#0, AOE-safe by UID — cf. JTL_140).
$onAttackAbilities["JTL_172:0"] = function($player, $mzID) {
    $unitObj = GetZoneObject($mzID);
    if ($unitObj === null || ($unitObj->removed ?? false)) return;
    $location = $unitObj->Location ?? 'GroundArena';
    $prefix   = (strpos($location, 'Space') !== false) ? 'Space' : 'Ground';
    $targets  = array_merge(
        ZoneSearch("my{$prefix}Arena",    ["Unit", "Leader Unit"]),
        ZoneSearch("their{$prefix}Arena", ["Unit", "Leader Unit"])
    );
    if (empty($targets)) return;
    $effectiveMax = min(2, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $effectiveMax . "|" . implode("&", $targets), 0,
        tooltip:"Deal_1_damage_to_each_of_up_to_2_units_in_this_arena");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_172#0", 0);
};

// Deal 1 to each chosen unit (up to 2). Snapshot UIDs first so a defeat that
// shifts arena indices can't misroute the second hit.
$customDQHandlers["JTL_172#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    $uids = array_slice($uids, 0, 2);
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
