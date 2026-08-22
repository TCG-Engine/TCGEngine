<?php
// TS26_76
// Cost 2 - Wartime Profiteer - [Cunning] - Power 3 - HP 3
// Text: When Defeated: Each opponent may ready a resource.

// TS26_76 Wartime Profiteer — When Defeated: each opponent may ready a resource. (2-player, mirrors SEC_215.)
$whenDefeatedAbilities["TS26_76:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "EACH opponent may ready a resource" — one independent offer per live opponent, each on their own
    // queue. Was OtherPlayer(): a single seat.
    // Offered only to seats that actually have an exhausted resource, so nobody is asked a question whose
    // only answer does nothing.
    foreach (OpponentsOf(intval($player)) as $opp) {
        $hasExh = false;
        foreach (GetResources($opp) as $r) { if (empty($r->removed) && intval($r->Status ?? 0) === 0) { $hasExh = true; break; } }
        if (!$hasExh) continue;
        DecisionQueueController::AddDecision($opp, "YESNO", "-", 1, tooltip: "Ready_a_resource?");
        DecisionQueueController::AddDecision($opp, "CUSTOM", "TS26_76#0", 1);
    }
};

$customDQHandlers["TS26_76#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $res = &GetResources(intval($player));
    for ($i = 0; $i < count($res); $i++) {
        if (empty($res[$i]->removed) && intval($res[$i]->Status ?? 0) === 0) { $res[$i]->Status = 1; break; }
    }
};
