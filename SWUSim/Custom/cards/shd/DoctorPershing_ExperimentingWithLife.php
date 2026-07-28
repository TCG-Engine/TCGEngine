<?php
// SHD_028
// Cost 2 - Doctor Pershing - Experimenting With Life - [Villainy,Vigilance] - Power 0 - HP 5
// Text: Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card.

// SHD_028 Doctor Pershing — Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card.
// The Exhaust is paid by SWUUnitAction; this closure pays the additional cost (deal 1 to a
// friendly unit — Pershing himself is always a valid target) then draws. Cost before effect.
$unitAbilities["SHD_028"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits('my');
    if (empty($targets)) { DoDrawCard(intval($player), 1); SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_friendly_unit", "DEAL_UNIT_DAMAGE|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DRAW_CARD|1", 1);
    SWUQueueAfterAction($player);
};
