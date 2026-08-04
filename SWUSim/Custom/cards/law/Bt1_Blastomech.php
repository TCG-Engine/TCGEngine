<?php
// LAW_173
// Cost 2 - BT-1 - Blastomech - [Aggression,Villainy] - Power 2 - HP 4
// Text: On Attack: Discard a card from your deck. If it's Aggression, you may deal 1 damage to a ground unit.

// LAW_173 BT-1 — On Attack: discard a card from your deck. If it's Aggression, you may deal 1 to a
// ground unit.
$onAttackAbilities["LAW_173:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null || strpos((string)(CardAspect($milled) ?? ''), 'Aggression') === false) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_1_to_a_ground_unit?", 'prompt' => "Deal_1_damage_to_a_ground_unit",
    ]);
};
