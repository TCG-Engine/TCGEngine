<?php
// SOR_215  |  Reprints: SHD_223
// Cost 1 - Snapshot Reflexes - [Cunning] - Upgrade Power 1 - Upgrade HP 1
// Text: When Played: You may attack with attached unit.

$customDQHandlers["SOR_215#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $unitMzID = $parts[1] ?? '';

    if ($lastDecision !== "YES" && $lastDecision !== "1") {
        // Declined — SWU_TRIGGER_RESUME handles SWUAfterAction.
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return;
    }

    $unitObj = GetZoneObject($unitMzID);
    if (SWUObjGone($unitObj)) {
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return;
    }

    // Unit must be ready (Status=1) to attack. BeginSWUAttack handles exhaust + target selection.
    if (intval($unitObj->Status) !== 1) {
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return;
    }

    BeginSWUAttack($player, $unitMzID);

    $playerID = $savedPID;
};
