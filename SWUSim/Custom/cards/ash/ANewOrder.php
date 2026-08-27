<?php
// ASH_264
// Cost 1 - A New Order
// Text: Give an Advantage token to each of up to 2 units.

// ASH_264 A New Order (event) — give an Advantage token to each of up to 2 units (any units).
$customDQHandlers["ASH_264#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) DoGiveAdvantageToken(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_264:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) $tg[] = $mz;
    }
    if (empty($tg)) return;
    $max = min(2, count($tg));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
        tooltip: "Give_an_Advantage_token_to_up_to_2_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_264#0", 1, dontSkipOnPass: 1);
};
