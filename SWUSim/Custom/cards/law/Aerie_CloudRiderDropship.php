<?php
// LAW_184
// Cost 6 - Aerie - Cloud-Rider Dropship - [Aggression,Heroism] - Power 3 - HP 7
// Text: On Attack: Deal 2 damage to an enemy ground unit and 2 damage to a base.

// LAW_184 Aerie — On Attack: deal 2 damage to an enemy ground unit and 2 damage to a base.
// "…and 2 damage to A BASE" names no controller, so per CR the attacker CHOOSES either base — the same
// reading already used by LAW_058 Honor-Bound Partisan and HMW_177 Adamant Ewoks. This previously dealt
// the 2 straight to the enemy base with no prompt, which also ran the two halves in the wrong order
// (base before the unit). Blocks 1/2 restore the printed order: unit first, then base.
$onAttackAbilities["LAW_184:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_2_to_an_enemy_ground_unit?", 'prompt' => "Deal_2_damage_to_an_enemy_ground_unit",
        'block' => 1,
    ]);
    SWUOfferBaseTarget(intval($player), [
        'continuation' => 'DEAL_BASE_DAMAGE', 'amount' => 2, 'baseSide' => 'any',
        'prompt' => "Deal_2_damage_to_a_base", 'block' => 2,
    ]);
};
