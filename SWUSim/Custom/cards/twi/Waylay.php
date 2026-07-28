<?php
// TWI_226
// Waylay
// Text: Return a non-leader unit to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_226:0"] = function($player, $mzID = '') {
    // Waylay — mandatory return of a non-leader unit (single valid target auto-resolves).
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true,
        'prompt' => "Choose_a_unit_to_return_to_hand",
    ]);
};
