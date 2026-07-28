<?php
// LAW_031
// Cost 4 - Bossk - Join Our Merry Band - [Vigilance,Command,Villainy] - Power 3 - HP 5
// Text: On Attack: Give a unit +1/+1 for this phase. You may give a unit -1/-1 for this phase.

// LAW_031 Bossk — On Attack: give a unit +1/+1 for this phase; you may give a unit -1/-1 for this
// phase. (MZMAYCHOOSE used for both — the OnAttack mandatory-MZCHOOSE trap.)
$onAttackAbilities["LAW_031:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Give_a_unit_+1/+1_for_this_phase?", "Choose_a_unit", "LAW_031#0");
};

$customDQHandlers["LAW_031#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($lastDecision, 1, 1, 'LAW_031');
    }
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Give_a_unit_-1/-1_for_this_phase?", "Choose_a_unit", "LAW_031#1");
};

$customDQHandlers["LAW_031#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($lastDecision, 1, 1, 'LAW_031D');
};
