<?php
// SHD_177
// Cost 3 - Vambrace Flamethrower - [Aggression] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Attack: You may deal 3 damage divided as you choose among enemy ground units."

// ─── SHD_177 Vambrace Flamethrower (granted On Attack) ────────────────────────
// Attached unit gains: "On Attack: You may deal 3 damage divided as you choose among enemy ground
// units." ($mzID = host attacker.) The MZSPLITASSIGN is queued from the CUSTOM continuation (safe in
// the OnAttack window, unlike a decision queued directly in the closure).
$onAttackAbilities["SHD_177:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemies = array_values(array_filter(ZoneSearch("theirGroundArena", AnyUnitFilter),
        fn($mz) => (($o = GetZoneObject($mz)) !== null && empty($o->removed))));
    if (empty($enemies)) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, tooltip:"Deal_3_damage_divided_among_enemy_ground_units?");
    // The host attacker is the dealer (the ability is GRANTED to it), so carry it across the YESNO as a
    // reindex-proof source token — CR 9.12.
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM',
        "SHD_177#0|" . _SWUEncodeDamageSource($mzID), 1);
};

$customDQHandlers["SHD_177#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    $srcMz = _SWUDecodeDamageSource((string)($parts[0] ?? ''));
    global $playerID; $playerID = intval($player);
    $enemies = array_values(array_filter(ZoneSearch("theirGroundArena", AnyUnitFilter),
        fn($mz) => (($o = GetZoneObject($mz)) !== null && empty($o->removed))));
    if (empty($enemies)) return;
    SWUOfferSplitDamage(intval($player), 3, $enemies, "Deal_3_damage_divided_among_enemy_ground_units",
        false, false, $srcMz);
};
