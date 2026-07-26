<?php
// JTL_253
// Cost 2 - Coordinated Front - [Heroism]
// Text: You may give a ground unit +2/+2 for this phase. / You may give a space unit +2/+2 for this phase.

// ── JTL_253 Coordinated Front (event continuation) — the SPACE half: you may give a space unit +2/+2. ─
$customDQHandlers["JTL_253#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $space = SWUAllUnits(null, SpaceArena);
    if (empty($space)) return;
    SWUQueueMayChooseTarget(intval($player), $space,
        "You_may_give_a_space_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_253");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_253:0"] = function($player, $mzID = '') {
// Coordinated Front — you may give a ground unit +2/+2, and you may give a
                          // space unit +2/+2 (two independent optional grants). Ground first, then the
                          // space half via the JTL_253 continuation.
            global $playerID;
            $playerID = intval($player);
            $ground = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("theirGroundArena", AnyUnitFilter));
            if (!empty($ground)) {
                SWUQueueMayChooseTarget(intval($player), $ground,
                    "You_may_give_a_ground_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_253");
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_253#0", 1);
            return;
};
