<?php
// SEC_131
// Cost 9 - Let's Talk - [Command]
// Text: If a friendly unit left play this phase, this event costs 3 resources less to play. / Each friendly unit captures an enemy non-leader unit in the same arena.

// SEC_131 Let's Talk — each friendly unit captures an enemy non-leader unit in the same arena. Loop over
// the friendly UIDs; for each, offer the enemy non-leader units in its arena.
$customDQHandlers["SEC_131#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uids = array_values(array_filter(explode(',', $parts[0] ?? ''), fn($x) => $x !== ''));
    while (!empty($uids)) {
        $captorUID = intval(array_shift($uids));
        $captor    = SWUFindMzByUID($captorUID);
        if ($captor === null) continue;
        $co = GetZoneObject($captor);
        if (SWUObjGone($co)) continue;
        $isSpace = strpos((string)($co->Location ?? ''), 'Space') !== false;
        $enemyZone = $isSpace ? 'theirSpaceArena' : 'theirGroundArena';
        $enemies = array_values(array_filter(ZoneSearch($enemyZone, NonLeaderUnitFilter),
            fn($mz) => ($e = GetZoneObject($mz)) !== null && empty($e->removed)));
        if (empty($enemies)) continue;   // no enemy in this arena → this friendly captures nothing
        $rest = implode(',', $uids);
        SWUQueueChooseTarget(intval($player), $enemies, "Capture_an_enemy_unit_in_the_same_arena", "SEC_131#1|{$captorUID}|{$rest}");
        return;   // resume the loop after this capture
    }
};

$customDQHandlers["SEC_131#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $captorUID = intval($parts[0] ?? 0);
    $rest      = $parts[1] ?? '';
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $captor = SWUFindMzByUID($captorUID);
        if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
    }
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_131#0|{$rest}", 1);   // next friendly
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_131:0"] = function($player, $mzID = '') {
// Let's Talk — "Each friendly unit captures an enemy non-leader unit in the same arena."
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (SWUFriendlyUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
            }
            if (empty($uids)) return;
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_131#0|" . implode(',', $uids), 1);
            return;
};
