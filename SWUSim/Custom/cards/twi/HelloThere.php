<?php
// TWI_052
// Hello There
// Text: Choose a unit that entered play this phase. It gets -4/-4 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_052:0"] = function($player, $mzID = '') {
// Hello There — "Choose a unit that entered play this phase. It gets -4/-4 for
                          // this phase." (SWU_PLAYED_UNIT_{uid} marks units that entered this phase.)
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'APPLY_PHASE_DEBUFF|4|4|TWI_052', 'side' => 'any',
                'extraFilter' => fn($o) => intval($o->Controller ?? 0) > 0
                    && GlobalEffectCount(intval($o->Controller ?? 0), 'SWU_PLAYED_UNIT_' . intval($o->UniqueID ?? -1)) > 0,
                'prompt' => "Give_a_unit_that_entered_this_phase_-4/-4",
            ]);
            return;
};
