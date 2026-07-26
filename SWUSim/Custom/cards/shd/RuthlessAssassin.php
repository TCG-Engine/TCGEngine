<?php
// SHD_235
// Cost 2 - Ruthless Assassin - [Villainy] - Power 3 - HP 3
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: Deal 2 damage to a friendly unit.

// ─── SHD_235 Ruthless Assassin ────────────────────────────────────────────────
// Overwhelm (auto) + When Played: Deal 2 damage to a friendly unit (mandatory; the Assassin itself is a
// valid friendly target).
$whenPlayedAbilities["SHD_235:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_a_friendly_unit", "DEAL_UNIT_DAMAGE|2");
};
