<?php
// JTL_060
// Cost 2 - Desperate Commando - [Vigilance] - Power 2 - HP 2
// Text: When Defeated: You may give a unit -1/-1 for this phase.

// ── JTL_060 Desperate Commando — When Defeated: You may give a unit -1/-1 for this phase. ─────────────
$whenDefeatedAbilities["JTL_060:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_a_unit_-1/-1_this_phase", "Give_-1/-1", "APPLY_PHASE_DEBUFF|1|1|JTL_060");
};
