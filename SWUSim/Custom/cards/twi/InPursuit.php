<?php
// TWI_221
// Cost 0 - In Pursuit - [Cunning]
// Text: Exhaust a friendly unit. If you do, exhaust an enemy unit.

// TWI_221 Grim Resolve (event continuation) — friendly unit exhausted; now exhaust an enemy unit.
$customDQHandlers["TWI_221#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    OnExhaustCard(intval($player), $lastDecision); // exhaust the chosen friendly unit
    $enemies = [];
    foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $enemies[] = $mz;
        }
    }
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_221:0"] = function($player, $mzID = '') {
// In Pursuit — "Exhaust a friendly unit. If you do, exhaust an enemy unit."
            global $playerID; $playerID = intval($player);
            $friendly = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $friendly[] = $mz;
                }
            }
            if (empty($friendly)) return; // no ready friendly unit → nothing happens
            SWUQueueChooseTarget(intval($player), $friendly, "Exhaust_a_friendly_unit", "TWI_221#0");
            return;
};
