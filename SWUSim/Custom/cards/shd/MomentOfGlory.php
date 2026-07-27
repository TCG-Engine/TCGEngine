<?php
// SHD_130
// Moment of Glory
// Text: Give a unit +4/+4 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_130:0"] = function($player, $mzID = '') {
// Moment of Glory — "Give a unit +4/+4 for this phase."
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'APPLY_PHASE_BUFF|4|4|SHD_130',
                'side'         => 'any',
                'prompt'       => "Give_a_unit_+4/+4_this_phase",
            ]);
            return;
};
