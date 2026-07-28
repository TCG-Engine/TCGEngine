<?php
// LAW_151
// Cost 1 - Profiteering Hunter - [Command] - Power 1 - HP 3
// Text: When Played: Another friendly unit gets +1/+1 for this phase.

// LAW_151 Profiteering Hunter — When Played: another friendly unit gets +1/+1 for this phase.
$whenPlayedAbilities["LAW_151:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|1|1|LAW_151',
        'side'         => 'my',
        'excludeSelf'  => true,
        'prompt'       => "Give_another_friendly_unit_+1/+1_for_this_phase",
    ]);
};
