<?php
// TWI_215
// Cost 5 - Geonosis Patrol Fighter - [Cunning] - Power 3 - HP 2
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / When Played: You may return a non-leader unit that costs 3 or less to its owner's hand.

// TWI_215 Geonosis Patrol Fighter — "Exploit 2. When Played: You may return a non-leader unit that
// costs 3 or less to its owner's hand."
$whenPlayedAbilities["TWI_215:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true, 'may' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID)) <= 3,
        'question' => "Return_a_non-leader_unit_costing_3_or_less_to_hand?", 'prompt' => "Choose_a_unit_to_return",
    ]);
};
