<?php
// SEC_206
// Cost 5 - Emissaries from Ryloth - [Cunning,Heroism] - Power 4 - HP 6
// Text: When Played: You may give a unit -3/-0 for this phase.

// SEC_206 Senator Riyo Chuchi — When Played: you may give a unit -3/-0 for this phase.
$whenPlayedAbilities["SEC_206:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_unit_-3/-0_for_this_phase?", "Choose_a_unit", "APPLY_PHASE_DEBUFF|3|0|");
};
