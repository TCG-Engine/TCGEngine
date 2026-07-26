<?php
// TS26_36
// Cost 10 - Tribunal - Grave of the 332nd - [Cunning,Vigilance] - Power 6 - HP 8
// Text: This unit costs 2 resources less to play for each other card you played this phase. / When Played: Give each other unit -2/-2 for this phase.

// TS26_36 Tribunal — When Played: give each OTHER unit -2/-2 for this phase (self excluded by UID).
$whenPlayedAbilities["TS26_36:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = ($self !== null) ? intval($self->UniqueID ?? -1) : -1;
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -2) !== $selfUID) {
                SWUApplyPhaseDebuff($mz, 2, 2, 'TS26_36');
            }
        }
    }
};
