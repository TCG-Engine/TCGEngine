<?php
// TWI_072
// Cost 1 - I Have the High Ground - [Vigilance]
// Text: Choose a friendly unit. Each enemy unit gets -4/-0 while attacking that unit this phase.

// TWI_072 I Have the High Ground — mark the chosen friendly unit so enemies attacking it get -4/-0
// this phase (consumed in SWUCombatDamage).
$customDQHandlers["TWI_072#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddTurnEffect($lastDecision, 'TWI_072'); // phase-duration marker
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_072:0"] = function($player, $mzID = '') {
// I Have the High Ground — "Choose a friendly unit. Each enemy unit gets -4/-0
                          // while attacking that unit this phase." (Marker read in SWUCombatDamage.)
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_friendly_unit_(enemies_attacking_it_get_-4/-0)", "TWI_072#0");
            return;
};
