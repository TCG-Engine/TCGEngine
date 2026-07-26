<?php
// IBH_021
// Cost 2 - Improvised Detonation - [Cunning]
// Text: Attack with a unit. It gets +2/+0 for this attack. (You can only attack with a ready friendly unit.)

$whenPlayedAbilities["IBH_021:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            $units[] = "{$zone}-{$i}";
        }
    }
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_attack_with", "IBH_021#0");
};
$whenPlayedAbilities["IBH_030:0"] = $whenPlayedAbilities["IBH_021:0"];

// IBH_021 / IBH_030 Improvised Detonation — give the chosen unit +2/+0 for this attack, then attack.
$customDQHandlers["IBH_021#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};
