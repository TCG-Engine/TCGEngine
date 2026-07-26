<?php
// ASH_200
// Cost 5 - Rehabilitation - [Cunning,Villainy]
// Text: Choose a non-leader unit. Give that unit -3/-0 for this phase, then take control of it. At the start of the regroup phase, its owner takes control of it.

// ASH_200 Rehabilitation — take control of the chosen non-leader unit until regroup, with -3/-0 this phase.
$customDQHandlers["ASH_200#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $newMz = SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($newMz === '') return;
    SWUApplyPhaseDebuff($newMz, 3, 0, 'ASH_200');   // -3/-0 for this phase
    AddTurnEffect($newMz, 'TEMPORARY_STEAL');        // owner regains control at RegroupPhaseStart
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_200:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Choose_a_non-leader_unit_to_rehabilitate", "ASH_200#0");
};
