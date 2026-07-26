<?php
// SEC_207
// Cost 5 - Lightmaker - I Have An Idea - [Cunning,Heroism] - Power 3 - HP 4
// Text: Raid 4 / When Defeated: Choose an arena. Exhaust each enemy unit in that arena.

// SEC_207 Lightmaker — Raid 4 + When Defeated: choose an arena; exhaust each enemy unit in that arena.
$whenDefeatedAbilities["SEC_207:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1, tooltip: "Choose_an_arena_(exhaust_each_enemy_unit_there)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SEC_207#0", 1);
};

$customDQHandlers["SEC_207#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'theirSpaceArena' : 'theirGroundArena';
    foreach (ZoneSearch($arena, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) OnExhaustCard(intval($player), $mz);
    }
};
