<?php
// SOR_078
// Vanquish
// Text: Defeat a non-leader unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_078:0"] = function($player, $mzID = '') {
    // Vanquish — "Defeat a non-leader unit."
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
        'prompt' => "Defeat_a_non-leader_unit",
    ]);
};
