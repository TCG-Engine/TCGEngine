<?php
// SOR_033  |  Reprints: SEC_030, SHD_030
// Cost 3 - Death Trooper - [Vigilance,Villainy] - Power 3 - HP 3
// Text: When Played: Deal 2 damage to a friendly ground unit and 2 damage to an enemy ground unit.

// SOR_033 Death Trooper — When Played: deal 2 to a friendly ground unit AND 2 to an enemy ground unit.
$whenPlayedAbilities["SOR_033:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $friendly = ZoneSearch("myGroundArena", AnyUnitFilter);
    if (!empty($friendly)) {
        SWUQueueChooseTarget(intval($player), $friendly, "Deal_2_to_a_friendly_ground_unit", "DEAL_UNIT_DAMAGE|2");
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_033#0", 1);
};

$customDQHandlers["SOR_033#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        "Deal_2_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|2", 0);
};

// ── SEC Phase 4: Damage / defeat ─────────────────────────────────────────────
// SEC_030 Death Trooper — When Played: deal 2 to a friendly ground unit AND 2 to an enemy ground unit.
$whenPlayedAbilities["SEC_030:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = ZoneSearch("myGroundArena", AnyUnitFilter);   // includes Death Trooper itself
    if (empty($friendly)) {   // no friendly ground → just the enemy half
        $enemy = ZoneSearch("theirGroundArena", AnyUnitFilter);
        if (empty($enemy)) return;
        SWUQueueChooseTarget(intval($player), $enemy, "Deal_2_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|2");
        return;
    }
    SWUQueueChooseTarget(intval($player), $friendly, "Deal_2_to_a_friendly_ground_unit", "SEC_030#0");
};

$customDQHandlers["SEC_030#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 2, intval($player));
    $enemy = ZoneSearch("theirGroundArena", AnyUnitFilter);
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Deal_2_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|2");
};

// ─── SHD_030 Death Trooper ────────────────────────────────────────────────────
// When Played: Deal 2 damage to a friendly ground unit AND 2 damage to an enemy ground unit. Two
// sequential mandatory targeted damages (Death Trooper itself always qualifies as the friendly target).
// Chained via SHD_030#0 so the enemy pick is queued from the CUSTOM continuation (safe $playerID frame),
// and so the enemy half still fires/fizzles regardless of the friendly pick.
$whenPlayedAbilities["SHD_030:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = [];
    foreach (ZoneSearch('myGroundArena', AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $friendly[] = $mz;
    }
    SWUQueueChooseTarget(intval($player), $friendly,
        "Deal_2_to_a_friendly_ground_unit", "SHD_030#0");
};

$customDQHandlers["SHD_030#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
    $enemy = [];
    foreach (ZoneSearch('theirGroundArena', AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $enemy[] = $mz;
    }
    SWUQueueChooseTarget(intval($player), $enemy,
        "Deal_2_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
