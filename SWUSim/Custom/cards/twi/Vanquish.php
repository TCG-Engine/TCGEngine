<?php
// TWI_077
// Vanquish
// Text: Defeat a non-leader unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_077:0"] = function($player, $mzID = '') {
// Vanquish — "Defeat a non-leader unit."
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
                'prompt' => "Defeat_a_non-leader_unit",
            ]);
};
