<?php
// SOR_019
// Security Complex - [Vigilance] - HP 25
// Text: 
// Epic Action: Give a Shield token to a non-leader unit.

// ── SOR_019 Security Complex — Base Epic Action ─────────────────────────────
// Resolves the shield-target choice queued by BaseAbilities.php.
$customDQHandlers["SOR_019#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision && $lastDecision !== "-") {
        GiveShieldToken($player, $lastDecision);
    }
    SWUAfterAction($player);
};

$baseAbilities["SOR_019"] = function($player) {
    $targets = array_merge(
        ZoneSearch("myGroundArena", ["Unit"]),
        ZoneSearch("theirGroundArena", ["Unit"]),
        ZoneSearch("mySpaceArena", ["Unit"]),
        ZoneSearch("theirSpaceArena", ["Unit"])
    );
    if (empty($targets)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Choose_a_non-leader_unit_to_shield");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_019#0", 1);
};
