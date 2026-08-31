<?php
// TWI_052
// Hello There
// Text: Choose a unit that entered play this phase. It gets -4/-4 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_052:0"] = function($player, $mzID = '') {
// Hello There — "Choose a unit that entered play this phase. It gets -4/-4 for
                          // this phase." ENTERED PLAY, so SWUUnitEnteredPlayThisPhase — a deployed leader
                          // and a created token both qualify. This used to read SWU_PLAYED_UNIT_, which is
                          // set only by ActivateCard, so a leader deployed that phase was invisible and the
                          // card offered NO target at all (bug #1025/#1026's family).
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'APPLY_PHASE_DEBUFF|4|4|TWI_052', 'side' => 'any',
                'extraFilter' => fn($o) => intval($o->Controller ?? 0) > 0
                    && SWUUnitEnteredPlayThisPhase($o),
                'prompt' => "Give_a_unit_that_entered_this_phase_-4/-4",
            ]);
            return;
};
