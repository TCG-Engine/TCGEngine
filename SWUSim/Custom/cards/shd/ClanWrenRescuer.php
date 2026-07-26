<?php
// SHD_040
// Cost 2 - Clan Wren Rescuer - [Heroism,Vigilance] - Power 1 - HP 2
// Text: When Played: Give an Experience token to a unit.

// ─── SHD_040 Clan Wren Rescuer ────────────────────────────────────────────────
// When Played: Give an Experience token to a unit (mandatory; the Rescuer itself is a valid target).
$whenPlayedAbilities["SHD_040:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_unit", "GIVE_EXPERIENCE|1");
};
