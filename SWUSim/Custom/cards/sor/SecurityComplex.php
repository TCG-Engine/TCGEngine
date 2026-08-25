<?php
// ⚠ Target pools use NonLeaderUnitFilter (Unit + TOKEN Unit): the printed text says "non-leader
// unit", and a bare ["Unit"] filter wrongly excluded TOKEN units too — "give a Shield token to a NON-LEADER unit" — a token unit can carry a Shield
// (the Open Fire filter-family sweep, 2026-08-13).
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
    // "Give a Shield token to A NON-LEADER UNIT" — unqualified, so every non-leader unit on the
    // table is a legal target (teammate's included in Team Suns).
    $targets = SWUAllUnits(null, null, NonLeaderUnitFilter);
    if (empty($targets)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Choose_a_non-leader_unit_to_shield");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_019#0", 1);
};
