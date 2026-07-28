<?php
// ASH_186
// Cost 2 - Treacherous Minefield - [Aggression]
// Text: Choose an arena. For this phase, each unit in that arena gains: "On Attack: Deal 2 damage to this unit."

// ASH_186 Treacherous Minefield — mark each unit in the chosen arena (both players) with the phase-
// duration ASH_186 marker ("On Attack: deal 2 to this unit"). Snapshot of units in play now.
$customDQHandlers["ASH_186#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    foreach (["my{$arena}", "their{$arena}"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) AddTurnEffect($mz, SWUMakeTurnEffect('ASH_186', [], SWU_DUR_PHASE));
        }
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_186:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Ground&Space", 1, tooltip: "Choose_an_arena");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_186#0", 1);
};
