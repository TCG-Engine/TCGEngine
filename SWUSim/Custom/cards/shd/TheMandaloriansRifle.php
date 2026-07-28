<?php
// SHD_251
// Cost 3 - The Mandalorian's Rifle - [Heroism] - Upgrade Power 3 - Upgrade HP 0
// Text: Attach to a friendly non-VEHICLE unit. / When Played: If attached unit is The Mandalorian, he captures an exhausted enemy non-leader unit. (Put the captured card facedown under him until he leaves play.)

// ─── SHD_251 The Mandalorian's Rifle (When Played as upgrade) ─────────────────
// When Played: If attached unit is The Mandalorian, he captures an exhausted enemy non-leader unit.
$whenPlayedAbilities["SHD_251:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (CardTitle($host->CardID ?? '') !== 'The Mandalorian') return;
    $hostUID = intval($host->UniqueID ?? 0);
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 0) $enemies[] = $mz;   // exhausted only
        }
    }
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Capture_an_exhausted_enemy_non-leader_unit", "SHD_251#0|{$hostUID}");
};

$customDQHandlers["SHD_251#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $host = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($host === null) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DoCaptureUnit(intval($player), $host, $lastDecision);
};
