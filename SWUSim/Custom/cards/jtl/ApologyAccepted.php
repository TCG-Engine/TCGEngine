<?php
// JTL_091
// Cost 1 - Apology Accepted - [Command,Villainy]
// Text: Defeat a friendly unit. You may give 2 Experience tokens to a unit.

// ── JTL_091 Apology Accepted (event continuation) — friendly defeated ($lastDecision); you may then
// give 2 Experience tokens to a unit. ────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_091#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_2_Experience_tokens_to_a_unit", "Give_2_Experience_tokens", "GIVE_EXPERIENCE|2");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_091:0"] = function($player, $mzID = '') {
// Apology Accepted — defeat a friendly unit; you may give 2 Experience tokens
                          // to a unit (continuation JTL_091).
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_friendly_unit", "JTL_091#0");
            return;
};
