<?php
// SHD_124
// Cost 2 - Legal Authority - [Command] - Upgrade Power 0 - Upgrade HP 2
// Text: Attach to a friendly unit. / When Played: Attached unit captures an enemy non-leader unit with less power than it. (Put the captured card facedown under attached unit until attached unit leaves play.)

// ─── SHD_124 Legal Authority (When Played as upgrade) ─────────────────────────
// When Played: Attached unit captures an enemy non-leader unit with less power than it. ($mzID = host.)
$whenPlayedAbilities["SHD_124:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $hostPower = intval(ObjectCurrentPower($host));
    $hostUID   = intval($host->UniqueID ?? 0);
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) < $hostPower) $enemies[] = $mz;
        }
    }
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Capture_an_enemy_non-leader_unit_with_less_power", "SHD_124#0|{$hostUID}");
};

$customDQHandlers["SHD_124#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $host = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($host === null) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DoCaptureUnit(intval($player), $host, $lastDecision);
};
