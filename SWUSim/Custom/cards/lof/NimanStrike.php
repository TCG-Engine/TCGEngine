<?php
// LOF_124
// Cost 1 - Niman Strike - [Command]
// Text: Attack with a Force unit, even if it's exhausted. It gets +1/+0 and can't attack bases for this attack.

// LOF_124 Niman Strike — the chosen Force unit attacks (even if exhausted) with +1/+0 and can't attack
// bases for this attack.
$customDQHandlers["LOF_124#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUAddAttackPowerBonus($lastDecision, 1);                 // +1/+0 for this attack
    BeginSWUAttack(intval($player), $lastDecision, true);     // noBases = true
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_124:0"] = function($player, $mzID = '') {
// Niman Strike — "Attack with a Force unit, even if it's exhausted. It gets +1/+0
                          // and can't attack bases for this attack."
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (TraitContains($u, 'Force')) $units[] = "{$zone}-{$i}"; // ready OR exhausted
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Attack_with_a_Force_unit_(+1/+0,_can't_attack_bases)", "LOF_124#0");
            return;
};
