<?php
// ASH_005
// Cost 7 - Luke Skywalker - I Can Save Him - [Vigilance,Heroism] - Power 6 - HP 7
// Text: When a friendly unit's attack ends: You may exhaust this leader. If you do, heal 1 damage from that unit.
// DeployText: When a friendly unit's attack ends: Heal 2 damage from that unit or from your base.
// Epic Action: If you control 7 or more resources, deploy this leader.

$customDQHandlers["ASH_005#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;   // declined → leader stays ready, no heal
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_005' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $mz = $parts[0] ?? '';
    if ($mz !== '' && str_contains($mz, '-')) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) OnHealUnit(intval($player), $mz, 1);
    }
};
