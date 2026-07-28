<?php
// SHD_131  |  Reprints: TWI_128
// Cost 3 - Take Captive - [Command]
// Text: A friendly unit captures an enemy non-leader unit in the same arena. (Put the captured card facedown under that unit until that unit leaves play.)

// SHD_131 / TWI_128 Take Captive — step 1: friendly capturer chosen ($lastDecision).
// Carry the capturer's UniqueID in the handler key so step 2 can re-resolve it.
$customDQHandlers["SHD_131#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);

    $captorObj = GetZoneObject($lastDecision);
    if (SWUObjGone($captorObj)) return;
    $captorUID = intval($captorObj->UniqueID ?? -1);

    // Determine the capturer's arena from its Location ("GroundArena" or "SpaceArena").
    $location = $captorObj->Location ?? 'GroundArena';
    $isSpace  = strpos($location, 'Space') !== false;
    $enemyZone = $isSpace ? 'theirSpaceArena' : 'theirGroundArena';

    // Eligible targets: enemy non-leader units in that same arena.
    $targets = array_values(array_filter(
        ZoneSearch($enemyZone, NonLeaderUnitFilter),
        function($emz) { $eo = GetZoneObject($emz); return $eo !== null && empty($eo->removed); }
    ));
    if (empty($targets)) return;   // fizzle — no valid target in that arena

    SWUQueueChooseTarget(intval($player), $targets,
        'Choose_an_enemy_non-leader_unit_to_capture', 'SHD_131#1|' . $captorUID, 0);
};

// SHD_131 / TWI_128 Take Captive — step 2: perform the capture.
// $parts[0] = capturer's UniqueID; $lastDecision = chosen captive's mzID.
$customDQHandlers["SHD_131#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);

    $captorUID = intval($parts[0] ?? -1);
    $captorMzID = SWUFindMzByUID($captorUID);
    if ($captorMzID === null) return;   // capturer left play before capture resolved

    CaptureUnit(intval($player), $captorMzID, $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_131:0"] = function($player, $mzID = '') {
// (identical reprint)
            global $playerID;
            $playerID = intval($player);
            // Build the list of friendly units that have at least one valid capture target:
            // an enemy NON-LEADER unit in the SAME arena (ground↔ground, space↔space).
            // Friendly Leader Units are allowed as capturers; captured unit must be non-leader.
            $capturers = [];
            foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZone => $theirZone) {
                $enemyNonLeaders = array_values(array_filter(
                    ZoneSearch($theirZone, NonLeaderUnitFilter),
                    function($emz) { $eo = GetZoneObject($emz); return $eo !== null && empty($eo->removed); }
                ));
                if (empty($enemyNonLeaders)) continue;
                foreach (ZoneSearch($myZone, AnyUnitFilter) as $fmz) {
                    $fo = GetZoneObject($fmz);
                    if (SWUObjGone($fo)) continue;
                    $capturers[] = $fmz;
                }
            }
            if (empty($capturers)) return;
            SWUQueueChooseTarget(intval($player), array_values(array_unique($capturers)),
                'Choose_a_friendly_unit_to_capture_with', 'SHD_131#0');
            return;
};

// ─── TWI_128 Take Captive (reprint of SHD_131) — identical When Played, reuses the SHD_131#0/#1 chain ───
$whenPlayedAbilities["TWI_128:0"] = function($player, $mzID = '') {
            global $playerID;
            $playerID = intval($player);
            $capturers = [];
            foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZone => $theirZone) {
                $enemyNonLeaders = array_values(array_filter(
                    ZoneSearch($theirZone, NonLeaderUnitFilter),
                    function($emz) { $eo = GetZoneObject($emz); return $eo !== null && empty($eo->removed); }
                ));
                if (empty($enemyNonLeaders)) continue;
                foreach (ZoneSearch($myZone, AnyUnitFilter) as $fmz) {
                    $fo = GetZoneObject($fmz);
                    if (SWUObjGone($fo)) continue;
                    $capturers[] = $fmz;
                }
            }
            if (empty($capturers)) return;
            SWUQueueChooseTarget(intval($player), array_values(array_unique($capturers)),
                'Choose_a_friendly_unit_to_capture_with', 'SHD_131#0');
            return;
};
