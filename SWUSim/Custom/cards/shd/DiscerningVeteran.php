<?php
// SHD_120
// Cost 5 - Discerning Veteran - [Command] - Power 3 - HP 4
// Text: When Played: This unit captures an enemy non-leader ground unit. (Put the captured card facedown under this unit until this unit leaves play.)

// ─── SHD_120 Discerning Veteran ───────────────────────────────────────────────
// When Played: This unit captures an enemy non-leader ground unit.
$whenPlayedAbilities["SHD_120:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('theirGroundArena', NonLeaderUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Capture_an_enemy_non-leader_ground_unit", "SHD_120#0|{$mzID}");
};

$customDQHandlers["SHD_120#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $captorMz = $parts[0] ?? '';
    if ($captorMz === '') return;
    DoCaptureUnit(intval($player), $captorMz, $lastDecision);
};
