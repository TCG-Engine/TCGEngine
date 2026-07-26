<?php
// JTL_178
// Cost 3 - Face Off - [Aggression]
// Text: If no player has taken the initiative this phase, you may ready an enemy unit. If you do, ready a friendly unit in the same arena.

// ── JTL_178 Face Off (event continuation) — ready the chosen enemy, then ready a friendly in the same
// arena. ─────────────────────────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_178#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    OnReadyCard(intval($player), $lastDecision); // ready the enemy
    $arena = (strpos($lastDecision, 'Space') !== false) ? 'mySpaceArena' : 'myGroundArena';
    $friendly = ZoneSearch($arena, AnyUnitFilter);
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly,
        "Ready_a_friendly_unit_in_the_same_arena", "READY_UNIT");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_178:0"] = function($player, $mzID = '') {
// Face Off — if no player has taken the initiative this phase, you may ready an
                          // enemy unit; if you do, ready a friendly unit in the same arena (cont JTL_178).
            global $playerID;
            $playerID = intval($player);
            if (strpos((string)GetInitiativeCounter(), 'UNCLAIMED') === false) return; // initiative taken
            $enemies = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($enemies)) return;
            SWUQueueMayChooseTarget(intval($player), $enemies,
                "You_may_ready_an_enemy_unit", "Ready_an_enemy_unit", "JTL_178#0");
            return;
};
