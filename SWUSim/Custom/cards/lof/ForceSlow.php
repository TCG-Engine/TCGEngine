<?php
// LOF_217
// Force Slow
// Text: Give an exhausted unit -8/-0 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_217:0"] = function($player, $mzID = '') {
// Force Slow — "Give an exhausted unit -8/-0 for this phase."
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'APPLY_PHASE_DEBUFF|8|0|LOF_217',
                'side' => 'any',
                'extraFilter' => fn($o) => intval($o->Status ?? 0) !== 1,  // exhausted only
                'prompt' => "Give_an_exhausted_unit_-8/-0_this_phase",
            ]);
};
