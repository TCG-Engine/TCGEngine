<?php
// LAW_079
// Cost 5 - K-2SO - Locking the Vault - [Aggression,Cunning,Heroism] - Power 3 - HP 5
// Text: Ambush / On Attack: You may deal 3 damage to a damaged ground unit.

// LAW_079 K-2SO — Ambush + On Attack: you may deal 3 damage to a damaged ground unit.
$onAttackAbilities["LAW_079:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3, 'arena' => 'Ground', 'may' => true,
        'extraFilter' => fn($o) => intval($o->Damage ?? 0) > 0,
        'question' => "Deal_3_to_a_damaged_ground_unit?", 'prompt' => "Deal_3_damage_to_a_damaged_ground_unit",
    ]);
};
