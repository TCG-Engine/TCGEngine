<?php
// TWI_105
// Cost 1 - Steadfast Senator - [Command] - Power 0 - HP 4
// Text: Action [2 resources, Exhaust]: Attack with a unit. It gets +2/+0 for this attack.

// TWI_105 Steadfast Senator continuation (the unit-Action itself is registered after `$unitAbilities = []`).
$customDQHandlers["TWI_105#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $a = GetZoneObject($lastDecision);
    if (SWUObjGone($a)) { SWUAfterAction(intval($player)); return; }
    SWUAddAttackPowerBonus($lastDecision, 2);       // +2/+0 for this attack
    BeginSWUAttack(intval($player), $lastDecision); // owns the after-action once it attacks
};

// TWI_105 Steadfast Senator — "Action [2 resources, Exhaust]: Attack with a unit. It gets +2/+0 for
// this attack." Choose any friendly ready unit → +2/+0 one-shot → attack. (Registered here, after
// `$unitAbilities = []`, so the entry isn't wiped.)
$unitActionCostKind["TWI_105"] = 'exhaust';

$unitActionResourceCosts["TWI_105"] = 2;

$unitAbilities["TWI_105"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
        }
    }
    if (empty($ready)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+2/+0)", "TWI_105#0");
};
