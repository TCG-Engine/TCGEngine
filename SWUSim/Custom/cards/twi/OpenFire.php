<?php
// TWI_174
// Open Fire
// Text: Deal 4 damage to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_174:0"] = function($player, $mzID = '') {
// Open Fire — "Deal 4 damage to a unit."
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 4,
        'prompt' => "Deal_4_damage_to_a_unit",
    ]);
};
