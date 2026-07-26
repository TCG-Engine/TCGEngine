<?php
// JTL_043
// Cost 5 - No Glory, Only Results - [Vigilance,Villainy]
// Text: Take control of a non-leader unit, then defeat it.

// ── JTL_043 No Glory, Only Results — take control of the chosen non-leader unit, then defeat it. ──────
$customDQHandlers["JTL_043#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $newMz = SWUTakeControlOfUnit(intval($player), $lastDecision);   // unit moves into the caster's arena
    if ($newMz === '') return;                                       // take control blocked (LAW_149 Rey) — nothing to defeat
    SWUDefeatUnit(intval($player), $newMz);                          // then defeat it: now friendly, so it lands in its owner's discard
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_043:0"] = function($player, $mzID = '') {
// No Glory, Only Results — "Take control of a non-leader unit, then defeat it."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    NonLeaderUnitFilter), ZoneSearch('mySpaceArena',    NonLeaderUnitFilter),
                ZoneSearch('theirGroundArena', NonLeaderUnitFilter), ZoneSearch('theirSpaceArena', NonLeaderUnitFilter)
            ));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_and_defeat_a_non-leader_unit", "JTL_043#0");
            return;
};
