<?php
// LAW_131
// Incapacitate
// Text: Give a unit -2/-2 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_131:0"] = function($player, $mzID = '') {
// Incapacitate — "Give a unit -2/-2 for this phase." Any unit.
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'APPLY_PHASE_DEBUFF|2|2|LAW_131',
                'side'         => 'any',
                'prompt'       => "Give_a_unit_-2/-2_for_this_phase",
            ]);
            return;
};
