<?php
// LAW_130
// Cost 2 - Betrayed Trust - [Vigilance]
// Text: Choose an enemy unit. For this phase, that unit can't deal combat damage.

// LAW_130 Betrayed Trust — tag the chosen enemy unit so it can't deal combat damage this phase.
$customDQHandlers["LAW_130#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddTurnEffect($lastDecision, 'NO_COMBAT_DAMAGE');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_130:0"] = function($player, $mzID = '') {
// Betrayed Trust — "Choose an enemy unit. For this phase, that unit can't deal
                          // combat damage." Tag it with the NO_COMBAT_DAMAGE marker (read in SWUCombatDamage).
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Choose_an_enemy_unit", "LAW_130#0");
            return;
};
