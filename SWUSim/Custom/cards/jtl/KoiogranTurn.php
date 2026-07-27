<?php
// JTL_179
// Koiogran Turn
// Text: Ready a Fighter or Transport unit with 6 or less power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_179:0"] = function($player, $mzID = '') {
// Koiogran Turn — ready a Fighter or Transport unit with 6 or less power.
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'READY_UNIT',
                'traits' => ['Fighter', 'Transport'],
                'extraFilter' => fn($o) => ObjectCurrentPower($o) <= 6,
                'prompt' => "Ready_a_Fighter/Transport_unit_with_6_or_less_power",
            ]);
};
