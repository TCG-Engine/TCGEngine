<?php
// SOR_116
// Cost 5 - Steadfast Battalion - [Command] - Power 5 - HP 5
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / On Attack: If you control a leader unit, give a friendly unit +2/+2 for this phase.

// ── SOR_116 Steadfast Battalion — On Attack ─────────────────────────────────
// "If you control a leader unit, give a friendly unit +2/+2 for this phase."
// The condition is a deployed friendly leader; the buff may target any friendly
// unit (including this attacker). $mzID is the attacker's mzID.
$onAttackAbilities["SOR_116:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) return;
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_friendly_unit_to_give_+2/+2');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'APPLY_PHASE_BUFF|2|2|SOR_116', 1);
};
