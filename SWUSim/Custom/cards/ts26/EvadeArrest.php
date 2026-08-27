<?php
// TS26_82
// Cost 3 - Evade Arrest - [Cunning]
// Text: Exhaust any number of non-<uq> units.

// TS26_82 Evade Arrest — exhaust each chosen non-unique unit.
$customDQHandlers["TS26_82#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        OnExhaustCard(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_82:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !CardUnique($o->CardID ?? '')) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    $max = count($tg);
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
        tooltip: "Exhaust_any_number_of_non-unique_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_82#0", 1, dontSkipOnPass: 1);
};
