<?php
// ASH_017
// Cost 6 - Greef Karga - Gracious Magistrate - [Cunning,Heroism] - Power 4 - HP 7
// Text: When you play or create a unit: You may exhaust this leader. If you do, give an Advantage token to that unit.
// DeployText: When you play or create a unit: Give an Advantage token to that unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["ASH_017#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    // "You may exhaust this leader. If you do…": an already-exhausted Greef cannot pay, so nothing happens.
    if (!_SWULeaderReadyUndeployed(intval($player), 'ASH_017')) return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_017' && empty($l->removed)) { $l->Ready = false; SWULogLeaderExhaustCost(intval($player), 'ASH_017'); break; } }
    unset($l);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) DoGiveAdvantageToken(intval($player), $mz); }
};

// Created-unit offer chain (armed by the token-creation path in GameLogic.php): offer the next still-in-play
// created unit, but only while Greef is ready; after its answer, re-arm for the rest.
$customDQHandlers["ASH_017#ASK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $key  = 'ASH017_CREATED_PENDING_' . intval($player);
    $list = array_values(array_filter(explode(',', GetSWUVar($key, '')), fn($x) => $x !== ''));
    if (!_SWULeaderReadyUndeployed(intval($player), 'ASH_017')) { SetSWUVar($key, ''); return; }
    $uid = 0;
    while (!empty($list)) {
        $u = intval(array_shift($list));
        if ($u > 0 && SWUFindMzByUID($u) !== null) { $uid = $u; break; }
    }
    SetSWUVar($key, implode(',', $list));
    if ($uid <= 0) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Greef_to_give_the_created_unit_an_Advantage_token?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_017#0|{$uid}", 1);
    if (!empty($list)) DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_017#ASK", 1);
};
