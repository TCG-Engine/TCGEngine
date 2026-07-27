<?php
// SHD_078
// Fell the Dragon
// Text: Defeat a non-leader unit with 5 or more power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_078:0"] = function($player, $mzID = '') {
    // Fell the Dragon — "Defeat a non-leader unit with 5 or more power."
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
        'extraFilter' => fn($o) => ObjectCurrentPower($o) >= 5,
        'prompt' => "Defeat_a_non-leader_unit_with_5+_power",
    ]);
};
