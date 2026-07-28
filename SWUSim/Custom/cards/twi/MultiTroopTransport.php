<?php
// TWI_066
// Cost 7 - Multi-Troop Transport - [Vigilance] - Power 3 - HP 6
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / On Attack: Create a Battle Droid token.

// TWI_066 Multi-Troop Transport — "On Attack: Create a Battle Droid token."
$onAttackAbilities["TWI_066:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T01', 1);
    // Combat owns the after-action.
};
