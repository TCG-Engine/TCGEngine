<?php
// LAW_213
// Cost 4 - Cutthroat Podracer - [Cunning,Villainy] - Power 4 - HP 4
// Text: When Played: You may deal 2 damage to an exhausted ground unit.

// LAW_213 Cutthroat Podracer — When Played: you may deal 2 damage to an exhausted ground unit.
$whenPlayedAbilities["LAW_213:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'arena' => 'Ground', 'may' => true,
        'extraFilter' => fn($o) => intval($o->Status ?? 0) === 0,   // exhausted
        'question' => "Deal_2_to_an_exhausted_ground_unit?", 'prompt' => "Deal_2_damage_to_a_unit",
    ]);
};
