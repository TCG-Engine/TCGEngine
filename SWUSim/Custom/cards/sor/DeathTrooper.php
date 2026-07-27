<?php
// SOR_033  |  Reprints: SEC_030, SHD_030
// Cost 3 - Death Trooper - [Vigilance,Villainy] - Power 3 - HP 3
// Text: When Played: Deal 2 damage to a friendly ground unit and 2 damage to an enemy ground unit.

// SOR_033 Death Trooper — When Played: deal 2 to a friendly ground unit AND 2 to an enemy ground unit.
$whenPlayedAbilities["SOR_033:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'my', 'arena' => 'Ground',
        'prompt' => "Deal_2_to_a_friendly_ground_unit",
    ]);
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_033#0", 1);
};

$customDQHandlers["SOR_033#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Deal_2_to_an_enemy_ground_unit", 'block' => 0,
    ]);
};

// ── SEC Phase 4: Damage / defeat ─────────────────────────────────────────────
// SEC_030 Death Trooper — When Played: deal 2 to a friendly ground unit AND 2 to an enemy ground unit.
$whenPlayedAbilities["SEC_030:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = ZoneSearch("myGroundArena", AnyUnitFilter);   // includes Death Trooper itself
    if (empty($friendly)) {   // no friendly ground → just the enemy half
        SWUOfferUnitTarget($player, $mzID, [
            'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'arena' => 'Ground',
            'prompt' => "Deal_2_to_an_enemy_ground_unit",
        ]);
        return;
    }
    SWUQueueChooseTarget(intval($player), $friendly, "Deal_2_to_a_friendly_ground_unit", "SEC_030#0");
};

$customDQHandlers["SEC_030#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 2, intval($player));
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Deal_2_to_an_enemy_ground_unit",
    ]);
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
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Deal_2_to_an_enemy_ground_unit",
    ]);
};
