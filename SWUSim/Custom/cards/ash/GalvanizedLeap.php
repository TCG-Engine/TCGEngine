<?php
// ASH_188
// Cost 4 - Galvanized Leap - [Aggression]
// Text: Ready a unit that was damaged this phase.

$whenPlayedAbilities["ASH_188:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                && in_array('SWU_DAMAGED_PHASE', $o->TurnEffects, true)) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Ready_a_unit_damaged_this_phase", "READY_UNIT");
};
