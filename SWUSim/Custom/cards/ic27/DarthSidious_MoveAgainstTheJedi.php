<?php
// IC27_026
// Cost 7 - Darth Sidious - Move Against the Jedi - [Vigilance,Villainy] - Unit (Ground) 5/8 (unique)
//   Traits: Force, Separatist, Sith
// Text: Restore 3 / When you heal damage from your base: Deal that much damage to an enemy unit.

// Restore 3 is auto-wired ($Restore_Cards). The reaction is armed inside OnHealBase (CombatLogic) —
// the ONE central point every base heal routes through (Restore, ability heals, base abilities) —
// which passes the amount ACTUALLY healed so a nearly-full base deals less than 3.
// Mandatory, not a "may": with one enemy unit the choose auto-resolves, with none it fizzles.
$customDQHandlers["IC27_026#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amount = intval($parts[0] ?? 0);
    if ($amount <= 0) return;
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {   // "an ENEMY unit" — both arenas
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (!SWUObjGone($o)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Deal_{$amount}_damage_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|{$amount}");
};
