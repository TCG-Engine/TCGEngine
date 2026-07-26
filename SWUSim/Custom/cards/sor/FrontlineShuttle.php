<?php
// SOR_110
// Cost 2 - Frontline Shuttle - [Command] - Power 1 - HP 3
// Text: Action [defeat this unit]: Attack with a unit, even if it's exhausted. It can't attack bases for this attack.

$unitActionCostKind["SOR_110"] = 'defeat';

// SOR_110 Frontline Shuttle — Action [defeat this unit]: Attack with a unit, even if it's
// exhausted. It can't attack bases for this attack. SWUUnitAction already defeated the
// Shuttle (the 'defeat' cost); pick a remaining friendly unit and attack with no-bases.
$unitAbilities["SOR_110"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $attackers = SWUUnitsWithNonBaseAttackTarget(intval($player)); // Shuttle already gone
    if (empty($attackers)) { SWUAfterAction($player); return; }
    if (count($attackers) === 1) { BeginSWUAttack(intval($player), $attackers[0], noBases: true); return; }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $attackers), 1, "Attack_with_a_unit_(it_can't_attack_bases)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_110#0", 1);
};

// BeginSWUAttack (combat) owns the after-action — do NOT append SWU_AFTER_ACTION.
$customDQHandlers["SOR_110#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction($player);
        return;
    }
    BeginSWUAttack(intval($player), $lastDecision, noBases: true);
};
