<?php
// JTL_262
// Evasive Maneuver
// Text: Evasive Maneuver

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_262:0"] = function($player, $mzID = '') {
// Evasive Maneuver — exhaust a unit.
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'EXHAUST_UNIT',
                'prompt' => "Exhaust_a_unit",
            ]);
};
