<?php
// SHD_018
// Cost 6 - The Mandalorian - Sworn To The Creed - [Cunning,Heroism] - Power 4 - HP 7
// Text: When you play an upgrade: You may exhaust this leader. If you do, exhaust an enemy unit with 4 or less remaining HP.
// DeployText: When you play an upgrade: You may exhaust an enemy unit with 6 or less remaining HP.
// Epic Action: If you control 6 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)

$customDQHandlers["SHD_018#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SHD_018' && empty($l->removed)) { $l->Ready = false; break; } }  // exhaust the leader (cost)
    unset($l);
    $maxHP   = intval($parts[0] ?? 4);
    $targets = _SWUEnemyUnitsRemainingHPAtMost(intval($player), $maxHP);
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");   // mandatory once the leader is exhausted
};
