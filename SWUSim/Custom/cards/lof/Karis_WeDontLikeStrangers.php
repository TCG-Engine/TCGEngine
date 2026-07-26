<?php
// LOF_031
// Cost 2 - Karis - We Don't Like Strangers - [Vigilance,Villainy] - Power 2 - HP 4
// Text: When Defeated: You may use the Force (lose your Force token). If you do, give a unit -2/-2 for this phase.

// LOF_031 Karis — When Defeated: may use the Force → give a unit -2/-2 for this phase.
$whenDefeatedAbilities["LOF_031:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_give_a_unit_-2/-2?", "LOF_031#0");
};

$customDQHandlers["LOF_031#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-2/-2_for_this_phase", "APPLY_PHASE_DEBUFF|2|2|LOF_031");
};
