<?php
// SHD_077
// Evidence of the Crime
// Text: Take control of an upgrade that costs 3 or less and attach it to an eligible unit of your choice.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_077:0"] = function($player, $mzID = '') {
// Take Control — "Take control of an upgrade that costs 3 or less and attach it to
                          // an eligible unit of your choice." (generic move-upgrade seam, cost≤3 filter)
            SWUQueueMoveUpgrade(intval($player), 'cost:3', "Take_control_of_an_upgrade_(cost_3_or_less)");
            return;
};
