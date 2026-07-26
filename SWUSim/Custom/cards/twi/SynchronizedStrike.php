<?php
// TWI_099
// Cost 2 - Synchronized Strike - [Command,Heroism]
// Text: Deal damage to an enemy unit equal to the number of units you control in its arena.

// TWI_099 Synchronized Strike (event continuation) — deal to the chosen enemy unit damage equal to the
// number of units the caster controls in that unit's arena (ground or space).
$customDQHandlers["TWI_099#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $target = GetZoneObject($lastDecision);
    if (SWUObjGone($target)) return;
    $zone = (strpos((string)$lastDecision, 'SpaceArena') !== false) ? 'mySpaceArena' : 'myGroundArena';
    $count = 0;
    foreach (ZoneSearch($zone, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $count++;
    }
    if ($count > 0) SWUDealDamageToUnit($lastDecision, $count, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_099:0"] = function($player, $mzID = '') {
// Synchronized Strike — "Deal damage to an enemy unit equal to the number of
                          // units you control in its arena." (Amount computed at resolution.)
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_damage_equal_to_your_units_in_its_arena", "TWI_099#0");
            return;
};
