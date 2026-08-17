<?php
// LAW_031
// Cost 4 - Bossk - Join Our Merry Band - [Vigilance,Command,Villainy] - Power 3 - HP 5
// Text: On Attack: Give a unit +1/+1 for this phase. You may give a unit -1/-1 for this phase.

// LAW_031 Bossk — On Attack: give a unit +1/+1 for this phase; you may give a unit -1/-1 for this
// phase.
//
// ⚠ ONLY THE SECOND SENTENCE CARRIES "you may". The +1/+1 is a MANDATORY targeted effect and must
// resolve whenever a legal target exists, so it is an MZCHOOSE; only the -1/-1 is an MZMAYCHOOSE.
// Both halves used to be MZMAYCHOOSE, justified by a comment citing "the OnAttack mandatory-MZCHOOSE
// trap" — re-probed 2026-08-16 and there is no such trap: a mandatory choose queued from an On Attack
// resolves normally (the same claim was disproved for base choices earlier in this port, where it had
// likewise been copied across three cards). The bug let a player decline the buff outright and attack
// at printed power.
//
// The two tooltips are deliberately DISTINCT. They used to be identical ("Choose_a_unit") over an
// identical pool, so the two consecutive prompts were indistinguishable in the pending state — which is
// what a "spurious duplicate prompt" report on this card actually was.
$onAttackAbilities["LAW_031:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_give_+1/+1", "LAW_031#0");
};

$customDQHandlers["LAW_031#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($lastDecision, 1, 1, 'LAW_031');
    }
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Give_a_unit_-1/-1_for_this_phase?", "Choose_a_unit_to_give_-1/-1", "LAW_031#1");
};

$customDQHandlers["LAW_031#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($lastDecision, 1, 1, 'LAW_031D');
};
