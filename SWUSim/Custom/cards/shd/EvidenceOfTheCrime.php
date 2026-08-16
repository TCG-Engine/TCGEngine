<?php
// SHD_077
// Evidence of the Crime
// Text: Take control of an upgrade that costs 3 or less and attach it to an eligible unit of your choice.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_077:0"] = function($player, $mzID = '') {
// Take Control — "Take control of an upgrade that costs 3 or less and attach it to
                          // an eligible unit of your choice." (generic move-upgrade seam, cost≤3 filter)
            // 'anyIncludingSource': the card says only "attach it to an eligible unit of your choice",
            // not "ANOTHER unit", so leaving the upgrade on its current host is legal - taking CONTROL
            // of it is the effect (USER RULING 2026-08-15).
            SWUQueueMoveUpgrade(intval($player), 'cost:3', "Take_control_of_an_upgrade_(cost_3_or_less)",
                '', 'anyIncludingSource');
            return;
};
