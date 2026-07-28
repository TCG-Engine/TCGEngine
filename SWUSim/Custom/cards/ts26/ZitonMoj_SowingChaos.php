<?php
// TS26_29
// Cost 4 - Ziton Moj - Sowing Chaos - [Aggression,Cunning,Villainy] - Power 4 - HP 4
// Text: Ambush (When you play this unit, he may attack an enemy unit.) / On Attack: For each player, deal 1 damage to a unit that player controls.

// TS26_29 Ziton Moj — Ambush. On Attack: for each player, deal 1 damage to a unit that player controls.
// (Queued via an intermediate CUSTOM so the mid-combat picks resolve under the caster — the OnAttack
// closure-level MZCHOOSE-skip only affects a decision queued directly in the closure.)
$onAttackAbilities["TS26_29:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_29#0", 1);
};

$customDQHandlers["TS26_29#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $friendly = SWUAllUnits('my');
    if (!empty($friendly)) {
        SWUQueueChooseTarget(intval($player), $friendly, "Deal_1_to_a_unit_you_control", "TS26_29#1");
        return;
    }
    // no friendly unit → straight to the enemy pick
    $enemy = SWUAllUnits('their');
    if (!empty($enemy)) SWUQueueChooseTarget(intval($player), $enemy, "Deal_1_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|1");
};

$customDQHandlers["TS26_29#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && str_contains($lastDecision, '-')) SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $enemy = SWUAllUnits('their');
    if (!empty($enemy)) SWUQueueChooseTarget(intval($player), $enemy, "Deal_1_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|1");
};
