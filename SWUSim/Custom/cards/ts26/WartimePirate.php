<?php
// TS26_66
// Cost 3 - Wartime Pirate - [Aggression] - Power 4 - HP 4
// Text: On Attack: An opponent deals 1 damage to a unit.

// TS26_66 Wartime Pirate — On Attack: an opponent deals 1 damage to a unit. (Cross-player: queued from a
// CUSTOM continuation so the opponent's pick resolves under the opponent's frame.)
$onAttackAbilities["TS26_66:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_66#0", 1);
};

$customDQHandlers["TS26_66#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget($opp, $tg, "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
    // leave $playerID = $opp so the opponent-frame mzIDs validate
};
