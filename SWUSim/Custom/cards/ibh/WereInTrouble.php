<?php
// IBH_061 / IBH_086
// Cost 3 - We're In Trouble - [Aggression]
// Text: Deal 3 damage to a unit.

$whenPlayedAbilities["IBH_061:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3, 'prompt' => "Deal_3_damage_to_a_unit",
    ]);
};
$whenPlayedAbilities["IBH_086:0"] = $whenPlayedAbilities["IBH_061:0"];
