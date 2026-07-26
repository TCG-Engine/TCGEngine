<?php
// LOF_035
// Cost 4 - Talzin's Assassin - [Vigilance,Villainy] - Power 4 - HP 4
// Text: When Played: You may use the Force (lose your Force token). If you do, give a unit -3/-3 for this phase.

// LOF_035 Talzin's Assassin — When Played: may use the Force → give a unit -3/-3 for this phase.
$whenPlayedAbilities["LOF_035:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_give_a_unit_-3/-3?", "LOF_035#0");
};

$customDQHandlers["LOF_035#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-3/-3_for_this_phase", "APPLY_PHASE_DEBUFF|3|3|LOF_035");
};
