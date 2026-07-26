<?php
// TWI_211
// Cost 3 - Sly Moore - Secretive Advisor - [Cunning] - Power 3 - HP 3
// Text: When Played: Take contol of an enemy token unit and ready it. At the start of the regroup phase, that token unit's owner takes control of it.

// TWI_211 Sly Moore — "When Played: Take control of an enemy token unit and ready it. At the start of the
// regroup phase, that token unit's owner takes control of it." (TEMPORARY_STEAL returns it at regroup.)
$whenPlayedAbilities["TWI_211:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tokens = [];
    foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, ['Token Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $tokens[] = $mz;
        }
    }
    if (empty($tokens)) return;
    SWUQueueChooseTarget(intval($player), $tokens, "Take_control_of_an_enemy_token_unit", "TWI_211#0");
};

$customDQHandlers["TWI_211#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $newMz = SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($newMz === '' || $newMz === null) return;
    $o = GetZoneObject($newMz);
    if ($o !== null) { $o->Status = 1; } // ready it
    AddTurnEffect($newMz, 'TEMPORARY_STEAL'); // owner regains control at regroup
};
