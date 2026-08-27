<?php
// JTL_210
// Cost 5 - The Mandalorian - Weathered Pilot - [Cunning,Cunning] - Power 5 - HP 6 - Upgrade Power 3 - Upgrade HP 1
// Text: When played as a unit: Exhaust up to 2 ground units. / Piloting [2 resources Cunning] / When played as an upgrade: Exhaust an enemy unit in this arena.

// JTL_210 The Mandalorian (pilot) — When played as a unit: Exhaust up to 2 ground units.
$whenPlayedAbilities["JTL_210:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $ground = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) $ground[] = $mz;
    }
    if (empty($ground)) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|2|" . implode("&", $ground), 1, "Exhaust_up_to_2_ground_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_210#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["JTL_210#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $o->Status = 0;
    }
};

// When played as an upgrade: Exhaust an enemy unit in this arena.
$whenPlayedAsUpgradeAbilities["JTL_210:0"] = function($player, $mzID) {
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $arena = (($host->Location ?? 'GroundArena') === 'SpaceArena') ? 'Space' : 'Ground';
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'side' => 'their', 'arena' => $arena,
        'prompt' => "Exhaust_an_enemy_unit_in_this_arena",
    ]);
};
