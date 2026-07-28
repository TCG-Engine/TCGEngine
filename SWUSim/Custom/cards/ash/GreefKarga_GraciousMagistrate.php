<?php
// ASH_017
// Cost 6 - Greef Karga - Gracious Magistrate - [Cunning,Heroism] - Power 4 - HP 7
// Text: When you play or create a unit: You may exhaust this leader. If you do, give an Advantage token to that unit.
// DeployText: When you play or create a unit: Give an Advantage token to that unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["ASH_017#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_017' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) DoGiveAdvantageToken(intval($player), $mz); }
};
