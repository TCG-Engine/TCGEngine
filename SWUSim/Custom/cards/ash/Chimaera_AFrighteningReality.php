<?php
// ASH_052
// Cost 7 - Chimaera - A Frightening Reality - [Vigilance,Villainy] - Power 6 - HP 6
// Text: When Played: You may choose a friendly unit and an enemy non-leader unit. If you do, defeat those units. / When an enemy unit is defeated: Heal 2 damage from your base.

// ASH_052 Chimaera — When Played: you may choose a friendly unit AND an enemy non-leader unit. If you do,
// defeat those units. (The "When an enemy unit is defeated: heal 2" reaction lives in SWUCollectLeavePlay-
// Reactions.) Two sequential picks; declining the first cancels both.
$whenPlayedAbilities["ASH_052:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = SWUAllUnits('my');
    $enemy = array_merge(ZoneSearch("theirGroundArena", ["Unit", "Token Unit"]), ZoneSearch("theirSpaceArena", ["Unit", "Token Unit"]));
    if (empty($friendly) || empty($enemy)) return;   // needs both → otherwise can't "choose ... and ..."
    SWUQueueMayChooseTarget(intval($player), $friendly, "Defeat_a_friendly_and_an_enemy_unit?", "Choose_a_friendly_unit", "ASH_052#0");
};

$customDQHandlers["ASH_052#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // declined → nothing
    $fObj = GetZoneObject($lastDecision);
    if (SWUObjGone($fObj)) return;
    $fuid = intval($fObj->UniqueID ?? 0);
    $enemy = array_merge(ZoneSearch("theirGroundArena", ["Unit", "Token Unit"]), ZoneSearch("theirSpaceArena", ["Unit", "Token Unit"]));
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Choose_an_enemy_non-leader_unit", "ASH_052#1|{$fuid}");
};

$customDQHandlers["ASH_052#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $eObj = GetZoneObject($lastDecision);
    $euid = ($eObj !== null && empty($eObj->removed)) ? intval($eObj->UniqueID ?? 0) : 0;
    $fmz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($fmz !== null) SWUDefeatUnit(intval($player), $fmz);            // friendly
    $emz = $euid > 0 ? SWUFindMzByUID($euid) : null;
    if ($emz !== null) SWUDefeatUnit(intval($player), $emz);            // enemy
};
