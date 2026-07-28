<?php
// ASH_211
// Cost 2 - Fateful Goodbye - [Cunning,Heroism]
// Text: If a friendly unit left play this phase, distribute 3 Advantage tokens among friendly units. If a friendly leader unit left play this phase, distribute 5 instead.

$whenPlayedAbilities["ASH_211:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $n = (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_LEADER_LEFT_PLAY') > 0) ? 5
       : ((GlobalEffectCount(intval($player), 'SWU_FRIENDLY_LEFT_PLAY') > 0) ? 3 : 0);
    if ($n <= 0) return;   // nothing left play → no distribute
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueDistributeAdvantage(intval($player), $n, $targets, false, "Distribute_{$n}_Advantage_among_friendly_units");
};
