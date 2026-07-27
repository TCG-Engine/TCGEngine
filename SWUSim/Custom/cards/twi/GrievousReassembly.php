<?php
// TWI_073
// Grievous Reassembly
// Text: Heal 3 damage from a unit. Create a Battle Droid token.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_073:0"] = function($player, $mzID = '') {
// Grievous Reassembly — "Heal 3 damage from a unit. Create a Battle Droid token."
            // Offer the heal BEFORE creating the token (so the new token isn't a heal target).
            SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>3,'prompt'=>"Heal_3_damage_from_a_unit"]);
            SWUCreateUnitToken(intval($player), 'TWI_T01'); // Battle Droid (unconditional)
};
