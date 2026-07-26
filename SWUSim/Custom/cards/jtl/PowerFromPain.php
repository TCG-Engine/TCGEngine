<?php
// JTL_042
// Cost 3 - Power from Pain - [Vigilance,Villainy]
// Text: Give a unit +1/+0 for this phase for each damage on it.

// ── JTL_042 Power from Pain (event continuation) — buff the chosen unit +N/+0 (N = damage on it). ─────
$customDQHandlers["JTL_042#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $n = intval($o->Damage ?? 0);
    if ($n <= 0) return; // no damage → +0/+0
    SWUApplyPhaseBuff($lastDecision, $n, 0, 'JTL_042');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_042:0"] = function($player, $mzID = '') {
// Power from Pain — give a unit +1/+0 this phase for each damage on it.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+1/+0_per_damage_on_it", "JTL_042#0");
            return;
};
