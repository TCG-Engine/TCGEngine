<?php
// LOF_028
// Tomb of Eilram - [Cunning] - HP 25
// Text: Action [exhaust a friendly unit]: The Force is with you (create your Force token).

// ── LOF_028 Tomb of Eilram — pay the cost (exhaust the chosen friendly unit), then create the Force. ──
$customDQHandlers["LOF_028#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision && $lastDecision !== "-" && $lastDecision !== "PASS") {
        OnExhaustCard(intval($player), $lastDecision);
        TheForceIsWithYou(intval($player));
    }
    SWUAfterAction(intval($player));
};

// LOF_028 Tomb of Eilram — "Action [exhaust a friendly unit]: The Force is with you (create your Force
// token)." Repeatable; the cost is exhausting one ready friendly unit (any arena, incl. a deployed
// leader unit). With no ready friendly unit the action is unavailable.
$baseActionRepeatable["LOF_028"] = true;

$baseAbilities["LOF_028"] = function($player) {
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Status ?? 0) === 1) $targets[] = $mz; // ready only — can't exhaust an exhausted unit
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Exhaust_a_friendly_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_028#0", 1);
};
