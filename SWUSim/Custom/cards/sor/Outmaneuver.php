<?php
// SOR_221
// Cost 3 - Outmaneuver - [Cunning]
// Text: Choose an arena (ground or space). Exhaust each unit in that arena.

// SOR_221 Outmaneuver (event) — receives the OPTIONCHOOSE arena pick; exhausts every unit in
// the chosen arena (both players). Queued by OnPlayEvent (CardEffects.php).
$customDQHandlers["SOR_221#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    foreach (array_merge(
        ZoneSearch("my{$arena}",    AnyUnitFilter),
        ZoneSearch("their{$arena}", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        OnExhaustCard($player, $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_221:0"] = function($player, $mzID = '') {
// Outmaneuver — "Choose an arena (ground or space). Exhaust each unit in that arena."
            global $playerID;
            $playerID = intval($player);
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1, "Choose_an_arena_to_exhaust");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_221#0", 1);
            return;
};
