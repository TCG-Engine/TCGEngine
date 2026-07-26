<?php
// TWI_067
// Cost 9 - The Zillo Beast - Awoken From The Depths - [Vigilance] - Power 10 - HP 10
// Text: When Played: Give each enemy ground unit -5/-0 for this phase. / When the regroup phase starts: Heal 5 damage from this unit.

// TWI_067 The Zillo Beast — "When Played: Give each enemy ground unit -5/-0 for this phase." (The
// regroup self-heal is handled in RegroupPhaseStart.)
$whenPlayedAbilities["TWI_067:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    foreach (ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($mz, 5, 0, 'TWI_067');
    }
};
