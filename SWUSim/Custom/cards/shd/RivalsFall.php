<?php
// SHD_079
// Rival's Fall
// Text: Defeat a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_079:0"] = function($player, $mzID = '') {
    // Rival's Fall — "Defeat a unit." (any unit, incl. deployed leaders)
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT',
        'prompt' => "Defeat_a_unit",
    ]);
};
