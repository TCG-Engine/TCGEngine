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
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_ground_unit", "DEAL_UNIT_DAMAGE|1");
    SWUQueueAfterAction($player);
};
