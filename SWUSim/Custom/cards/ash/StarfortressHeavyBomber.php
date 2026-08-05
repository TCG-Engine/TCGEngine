<?php
// ASH_174
// Cost 5 - StarFortress Heavy Bomber - [Aggression] - Power 3 - HP 3
// Text: When Played: You may deal 6 damage to a non-<uq> ground unit.

// ASH_174 StarFortress Heavy Bomber — When Played: you may deal 6 damage to a non-unique ground unit.
$whenPlayedAbilities["ASH_174:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 6, 'may' => true, 'arena' => 'Ground',
        'extraFilter' => fn($o) => !CardUnique($o->CardID ?? ''),
        'question' => "Deal_6_to_a_non-unique_ground_unit?", 'prompt' => "Deal_6_damage_to_a_non-unique_ground_unit",
    ]);
};
