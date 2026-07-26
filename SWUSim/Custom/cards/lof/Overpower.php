<?php
// LOF_126
// Cost 3 - Overpower - [Command]
// Text: Give a unit +3/+3 and Overwhelm for this phase. (When attacking an enemy unit, deal excess damage to the opponent's base.)

// LOF_126 Overpower — give the chosen unit +3/+3 and Overwhelm for this phase.
$customDQHandlers["LOF_126#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUApplyPhaseBuff($lastDecision, 3, 3, 'LOF_126');
    AddTurnEffect($lastDecision, 'OVERWHELM^LOF_126');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_126:0"] = function($player, $mzID = '') {
// Overpower — "Give a unit +3/+3 and Overwhelm for this phase."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+3/+3_and_Overwhelm_this_phase", "LOF_126#0");
            return;
};
