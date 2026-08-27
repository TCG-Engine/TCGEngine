<?php
// ASH_142
// Cost 2 - Mortar Trooper - [Aggression,Villainy] - Power 1 - HP 4
// Text: Action [Exhaust]: Deal 1 damage to each of up to 3 ground units.

// ASH_142 Mortar Trooper — Action [Exhaust]: deal 1 damage to each of up to 3 ground units.
$unitAbilities["ASH_142"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits(null, GroundArena);
    if (empty($tg)) { SWUAfterAction($player); return; }
    $max = min(3, count($tg));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1, tooltip: "Deal_1_to_up_to_3_ground_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ASH_142#0", 1, dontSkipOnPass: 1);
    SWUQueueAfterAction($player);
};

$customDQHandlers["ASH_142#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    // Snapshot UIDs first (defeats reindex the zones), then deal 1 to each.
    $uids = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player)); }
};
