<?php
// TS26_70
// Cost 3 - Backed by Black Sun - [Aggression]
// Text: Deal 1 damage to an enemy unit. / You may deal damage to a unit equal to the number of damaged enemy units.

// TS26_70 Backed by Black Sun — deal 1 to the chosen enemy, then MAY deal (# damaged enemy units) to a unit.
$customDQHandlers["TS26_70#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && str_contains($lastDecision, '-')) SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $count = 0;
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $count++;
    }
    if ($count <= 0) return;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $count, 'may' => true,
        'question' => "Deal_{$count}_damage_to_a_unit?", 'prompt' => "Deal_{$count}_damage_to_a_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_70:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $enemy = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Deal_1_damage_to_an_enemy_unit", "TS26_70#0");
};
