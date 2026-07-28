<?php
// LOF_224
// Cost 2 - Pounce - [Cunning]
// Text: Attack with a Creature unit. It gets +4/+0 for this attack.

// LOF_224 Pounce — the chosen Creature unit attacks with +4/+0 (can attack bases normally).
$customDQHandlers["LOF_224#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUAddAttackPowerBonus($lastDecision, 4);
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_224:0"] = function($player, $mzID = '') {
// Pounce — "Attack with a Creature unit. It gets +4/+0 for this attack."
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (TraitContains($u, 'Creature') && intval($u->Status ?? 0) === 1) $units[] = "{$zone}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Attack_with_a_Creature_unit_(+4/+0)", "LOF_224#0");
            return;
};
