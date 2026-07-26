<?php
// JTL_140
// Cost 4 - IG-2000 - Assassin's Aggressor - [Aggression,Villainy] - Power 3 - HP 4
// Text: Overwhelm / When Played: Deal 1 damage to each of up to 3 units.

// ── JTL_140 IG-2000 — Overwhelm (auto-wired) + When Played: Deal 1 damage to each of up to 3 units. ──
$whenPlayedAbilities["JTL_140:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    $effectiveMax = min(3, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $effectiveMax . "|" . implode("&", $targets), 1, tooltip: "Deal_1_damage_to_each_of_up_to_3_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_140#0", 1);
};

$customDQHandlers["JTL_140#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    // Snapshot UIDs of the picks (cap at 3), then deal 1 to each (AOE-safe: a defeat shifts indices).
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    $uids = array_slice($uids, 0, 3);
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
