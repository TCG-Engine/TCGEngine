<?php
// ASH_136
// Cost 2 - Display of Strength - [Command]
// Text: Give a unit +3/+3 for this phase.

$whenPlayedAbilities["ASH_136:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|3|3|ASH_136', 'side' => 'any',
        'prompt' => "Give_a_unit_+3/+3_this_phase",
    ]);
};
