<?php
// TS26_76
// Cost 2 - Wartime Profiteer - [Cunning] - Power 3 - HP 3
// Text: When Defeated: Each opponent may ready a resource.

// TS26_76 Wartime Profiteer — When Defeated: each opponent may ready a resource. (2-player, mirrors SEC_215.)
$whenDefeatedAbilities["TS26_76:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $hasExh = false;
    foreach (GetResources($opp) as $r) { if (empty($r->removed) && intval($r->Status ?? 0) === 0) { $hasExh = true; break; } }
    if (!$hasExh) return;
    DecisionQueueController::AddDecision($opp, "YESNO", "-", 1, tooltip: "Ready_a_resource?");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "TS26_76#0", 1);
};

$customDQHandlers["TS26_76#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $res = &GetResources(intval($player));
    for ($i = 0; $i < count($res); $i++) {
        if (empty($res[$i]->removed) && intval($res[$i]->Status ?? 0) === 0) { $res[$i]->Status = 1; break; }
    }
};
