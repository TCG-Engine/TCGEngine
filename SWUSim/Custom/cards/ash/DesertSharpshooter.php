<?php
// ASH_170
// Cost 3 - Desert Sharpshooter - [Aggression] - Power 3 - HP 3
// Text: When Played: You may deal 2 damage to an upgraded ground unit.

// ASH_170 Desert Sharpshooter — When Played: you may deal 2 damage to an upgraded ground unit.
$whenPlayedAbilities["ASH_170:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'may' => true, 'arena' => 'Ground',
        'extraFilter' => fn($o) => _SWUIsUpgraded($o),
        'question' => "Deal_2_to_an_upgraded_ground_unit?", 'prompt' => "Choose_an_upgraded_ground_unit",
    ]);
};
