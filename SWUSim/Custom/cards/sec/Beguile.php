<?php
// SEC_233
// Beguile
// Text: Look at an opponent's hand. Then, choose a non-leader unit that opponent controls that costs 6 or less and return it to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_233:0"] = function($player, $mzID = '') {
    // Beguile — Look at an opponent's hand; choose a non-leader enemy unit that costs 6 or less and
    // return it to its owner's hand.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'side' => 'their', 'nonLeader' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID ?? '')) <= 6,
        'prompt' => "Return_an_enemy_non-leader_unit_(cost_6_or_less)_to_hand",
    ]);
};
