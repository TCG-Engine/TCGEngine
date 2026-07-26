<?php
// JTL_160
// Cost 2 - Supporting Eta-2 - [Aggression] - Power 2 - HP 2
// Text: On Attack: You may give a ground unit +2/+0 for this phase.

// ── JTL_160 Supporting Eta-2 — On Attack: You may give a GROUND unit +2/+0 for this phase. ───────────
$onAttackAbilities["JTL_160:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_a_ground_unit_+2/+0", "Give_+2/+0_this_phase", "APPLY_PHASE_BUFF|2|0|JTL_160");
};
