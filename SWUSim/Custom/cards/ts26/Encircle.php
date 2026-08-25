<?php
// TS26_61
// Cost 5 - Encircle - [Command]
// Text: This event costs 1 resource less to play for each friendly unit. / A friendly unit captures an enemy non-leader unit in the same arena. (Put the captured card facedown under that unit until that unit leaves play.)

// TS26_61 Encircle — a friendly unit captures an enemy non-leader unit in the SAME arena.
$customDQHandlers["TS26_61#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $captorMz = $lastDecision;
    $captor = GetZoneObject($captorMz);
    $captorUID = SWUObjUID($captor);
    $arena = strpos($captorMz, 'SpaceArena') !== false ? 'theirSpaceArena' : 'theirGroundArena';
    $tg = [];
    foreach (ZoneSearch($arena, NonLeaderUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Capture_an_enemy_non-leader_unit_(same_arena)", "TS26_27#0|" . $captorUID);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_61:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $captors = SWUFriendlyUnits();
    $valid = [];
    foreach ($captors as $mz) {
        $arena = strpos($mz, 'SpaceArena') !== false ? 'theirSpaceArena' : 'theirGroundArena';
        if (!empty(ZoneSearch($arena, NonLeaderUnitFilter))) $valid[] = $mz;
    }
    if (empty($valid)) return;
    SWUQueueChooseTarget(intval($player), $valid, "Choose_a_friendly_unit_to_capture_with", "TS26_61#0");
};
