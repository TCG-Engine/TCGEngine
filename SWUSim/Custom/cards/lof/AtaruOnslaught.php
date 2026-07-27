<?php
// LOF_174
// Ataru Onslaught
// Text: Ready a Force unit with 4 or less power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_174:0"] = function($player, $mzID = '') {
// Ataru Onslaught — "Ready a Force unit with 4 or less power."
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'READY_UNIT', 'traits' => 'Force',
        'extraFilter' => fn($o) => intval(ObjectCurrentPower($o)) <= 4,
        'prompt' => "Ready_a_Force_unit_with_4_or_less_power",
    ]);
};
