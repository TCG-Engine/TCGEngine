<?php
// SEC_127
// Cost 3 - Charged with Corruption - [Command]
// Text: You may disclose CommandCommand (reveal cards from your hand with these aspect icons among them). If you do, a friendly unit captures an enemy non-leader unit. (Put the captured card facedown under that unit until that unit leaves play.)

// SEC_127 Charged with Corruption — disclose CommandCommand → a friendly unit captures an enemy
// non-leader unit. #0 picks the captor, #1 picks the enemy, then capture.
$customDQHandlers["SEC_127#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_capturing_unit", "SEC_127#1");
};

$customDQHandlers["SEC_127#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = GetZoneObject($lastDecision);
    if (SWUObjGone($captor)) return;
    $captorUID = intval($captor->UniqueID ?? 0);
    $enemies = array_merge(ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter));
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Capture_an_enemy_non-leader_unit", "SEC_253#0|{$captorUID}");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_127:0"] = function($player, $mzID = '') {
// Charged with Corruption — disclose CommandCommand → a friendly unit captures
                          // an enemy non-leader unit.
            SWUQueueDisclose(intval($player), ['Command', 'Command'], "SEC_127#0",
                "Disclose_CommandCommand_to_capture_an_enemy_unit");
            return;
};
