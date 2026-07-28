<?php
// TWI_170
// Daring Raid
// Text: Deal 2 damage to a unit or base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_170:0"] = function($player, $mzID = '') {
// Daring Raid — "Deal 2 damage to a unit or base."
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'DEAL_TARGET', 'amount' => 2, 'includeBases' => true,
                'prompt' => "Deal_2_damage_to_a_unit_or_base",
            ]);
};
