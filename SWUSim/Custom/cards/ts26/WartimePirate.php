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
    global $playerID; $playerID = intval($player);
    // "AN opponent deals 1 damage to a unit" — the controller chooses WHO deals it.
    // ⚠ NO $eligible filter: the target pool is "a unit", unqualified, so it spans EVERY seat's board and
    // is IDENTICAL whichever opponent is chosen. No opponent can be pre-filtered as unable to act. The
    // only guard is board-level — if there is no unit anywhere, nobody can do anything. (Same shape as
    // TS26_54: gate once globally, filter nobody.)
    if (empty(SWUAllUnits())) return;
    SWUQueueChooseOpponent(intval($player), 'TS26_66#1',
        "Choose_an_opponent_to_deal_1_damage");
};

$customDQHandlers["TS26_66#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    $playerID = $opp;
    $tg = SWUAllUnits();
    if (empty($tg)) { $playerID = intval($player); return; }
    SWUQueueChooseTarget($opp, $tg, "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
    // leave $playerID = $opp so the opponent-frame mzIDs validate
};
