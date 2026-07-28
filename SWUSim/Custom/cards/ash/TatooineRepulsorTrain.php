<?php
// ASH_035
// Cost 7 - Tatooine Repulsor Train - [Command,Aggression] - Power 8 - HP 7
// Text: This unit can't be attacked while you control 2 or more exhausted units (unless it gains Sentinel). / On Attack: Deal 2 damage to a ground unit for each friendly exhausted unit.

// ASH_035 Tatooine Repulsor Train — On Attack: deal 2 damage to a ground unit for each friendly
// exhausted unit. (Amount computed at resolution; the chosen unit takes 2 × exhausted-count.)
$onAttackAbilities["ASH_035:0"] = function($player, $mzID) {
    $exhausted = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->Status ?? 1) === 0) $exhausted++;
    }
    if ($exhausted <= 0) return;   // no exhausted units → 0 damage, nothing to do
    $amt = 2 * $exhausted;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $amt, 'arena' => 'Ground',
        'prompt' => "Deal_{$amt}_to_a_ground_unit",
    ]);
};
