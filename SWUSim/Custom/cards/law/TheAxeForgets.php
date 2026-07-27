<?php
// LAW_246
// The Axe Forgets
// Text: Return a non-leader unit that costs 3 or less to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_246:0"] = function($player, $mzID = '') {
    // The Axe Forgets — "Return a non-leader unit that costs 3 or less to its owner's hand."
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID ?? '')) <= 3,
        'prompt' => "Return_a_non-leader_unit_costing_3_or_less",
    ]);
};
