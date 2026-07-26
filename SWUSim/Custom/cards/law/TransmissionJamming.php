<?php
// LAW_243
// Cost 1 - Transmission Jamming - [Cunning]
// Text: Name a card. Cards with that name can't be played this phase.

// LAW_243 Transmission Jamming — name a card; it can't be played this phase (both players).
$customDQHandlers["LAW_243#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    $encName = str_replace(' ', '_', trim($lastDecision));
    AddGlobalEffects(intval($player), "SWU_NAMEBLOCK_PHASE|{$encName}");
    AddGameLogEntry('NAMECARD', 'P' . intval($player) . ' named ' . trim($lastDecision) . " (can't be played this phase)", 'ALL');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_243:0"] = function($player, $mzID = '') {
// Transmission Jamming — "Name a card. Cards with that name can't be played
                          // this phase." (phase-duration, applies to BOTH players).
            global $playerID; $playerID = intval($player);
            DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card_(can't_be_played_this_phase)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_243#0", 1);
            return;
};
