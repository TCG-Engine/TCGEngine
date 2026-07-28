<?php
// SEC_215
// Cost 2 - Emissary's Sheathipede - [Cunning] - Power 2 - HP 4
// Text: When Defeated: Each opponent may ready a resource.

// SEC_215 Emissary's Sheathipede — When Defeated: each opponent may ready a resource.
$whenDefeatedAbilities["SEC_215:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Twin Suns (Phase 3): EACH opponent may ready a resource (2-player: the one opponent). Each decides
    // independently — `continue` (not `return`) so a skipped opponent doesn't cut off the rest.
    foreach (OpponentsOf(intval($player)) as $opp) {
        $hasExh = false;
        foreach (GetResources($opp) as $r) { if (empty($r->removed) && intval($r->Status ?? 0) === 0) { $hasExh = true; break; } }
        if (!$hasExh) continue;
        DecisionQueueController::AddDecision($opp, "YESNO", "-", 1, tooltip: "Ready_a_resource?");
        DecisionQueueController::AddDecision($opp, "CUSTOM", "SEC_215#0", 1);
    }
};

$customDQHandlers["SEC_215#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $res = &GetResources(intval($player));
    for ($i = 0; $i < count($res); $i++) {
        if (empty($res[$i]->removed) && intval($res[$i]->Status ?? 0) === 0) { $res[$i]->Status = 1; break; }
    }
};
