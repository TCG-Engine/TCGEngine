<?php
// SEC_254
// Cost 4 - Heroic ARC-170 - [Heroism] - Power 4 - HP 3
// Text: When Played: If you control a damaged unit, you may deal 2 damage to an enemy unit.

// SEC_254 Heroic ARC-170 — When Played: if you control a damaged unit, you may deal 2 to an enemy unit.
$whenPlayedAbilities["SEC_254:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hasDamaged = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->Damage ?? 0) > 0) { $hasDamaged = true; break; }
    }
    if (!$hasDamaged) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'may' => true,
        'question' => "Deal_2_to_an_enemy_unit?", 'prompt' => "Choose_an_enemy_unit",
    ]);
};
