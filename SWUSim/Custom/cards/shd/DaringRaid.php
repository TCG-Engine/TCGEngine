<?php
// SHD_178
// Daring Raid
// Text: Deal 2 damage to a unit or base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_178:0"] = function($player, $mzID = '') {
// Daring Raid — "Deal 2 damage to a unit or base."
            $targets = _SWUAllUnitsAndBases(intval($player));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_a_unit_or_base", "DEAL_TARGET|2");
            return;
};
