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
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    $targets = SWUFriendlyUnits();
    if (empty($targets)) { DoDrawCard(intval($player), 1); SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_friendly_unit", "DEAL_UNIT_DAMAGE|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DRAW_CARD|1", 1);
    SWUQueueAfterAction($player);
};
