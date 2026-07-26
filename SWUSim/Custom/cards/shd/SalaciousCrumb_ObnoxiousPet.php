<?php
// SHD_080
// Cost 1 - Salacious Crumb - Obnoxious Pet - [Command,Villainy] - Power 1 - HP 3
// Text: When Played: Heal 1 damage from your base. / Action [Exhaust, return this unit to his owner's hand]: Deal 1 damage to a ground unit.

// ─── SHD_080 Salacious Crumb ──────────────────────────────────────────────────
// When Played: Heal 1 damage from your base. + Action [Exhaust, return this unit to his owner's hand]:
// Deal 1 damage to a ground unit. (The Exhaust is paid by SWUUnitAction; the closure pays the additional
// return-to-hand cost, then deals the damage.)
$whenPlayedAbilities["SHD_080:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 1);
};

$unitActionCostKind["SHD_080"] = 'exhaust';

$unitAbilities["SHD_080"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUBounceUnit(intval($player), $mzID);                          // additional cost: return this unit to hand
    $targets = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_ground_unit", "DEAL_UNIT_DAMAGE|1");
    SWUQueueAfterAction($player);
};
