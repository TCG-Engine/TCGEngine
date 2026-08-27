<?php
// ASH_205
// Cost 3 - Inspiring Veteran - [Cunning,Heroism] - Power 3 - HP 3
// Text: When Played: Give an Advantage token to each of up to 3 exhausted units.

// ASH_205 Inspiring Veteran — When Played: give an Advantage token to each of up to 3 exhausted units
// (friendly or enemy; the just-played ASH_205 itself is exhausted and eligible).
$whenPlayedAbilities["ASH_205:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    $max = min(3, count($targets));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $targets), 1,
        tooltip: "Give_an_Advantage_token_to_up_to_3_exhausted_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_205#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["ASH_205#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) DoGiveAdvantageToken(intval($player), $mz);
    }
};
