<?php
// SEC_228
// Cost 1 - Accelerate Our Plans - [Cunning]
// Text: Exhaust a friendly unit. If you do, attack with another unit. It gets +3/+0 for this attack.

// SEC_228 Clever Gambit (event) — Exhaust a friendly unit. If you do, attack with another unit. It
// gets +3/+0 for this attack. Step 1: pick the unit to exhaust (the cost).
$customDQHandlers["SEC_228#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $exhaustedUID = intval($o->UniqueID ?? 0);
    $o->Status = 0;   // exhaust (pay the cost)
    $attackers = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1 && intval($u->UniqueID ?? 0) !== $exhaustedUID) $attackers[] = "{$zone}-{$i}";
        }
    }
    if (empty($attackers)) return;
    SWUQueueChooseTarget(intval($player), $attackers, "Attack_with_another_unit_(+3/+0)", "SEC_228#1");
};

$customDQHandlers["SEC_228#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($obj)) { $playerID = $savedPID; return; }
    SWUAddAttackPowerBonus($mz, 3);
    BeginSWUAttack(intval($player), $mz);   // combat owns SWUAfterAction
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_228:0"] = function($player, $mzID = '') {
// Clever Gambit — Exhaust a friendly unit. If you do, attack with another unit.
                          // It gets +3/+0 for this attack. Need ≥2 ready units (one to exhaust + attacker).
            global $playerID; $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (count($ready) < 2) return;
            SWUQueueChooseTarget($player, $ready, "Exhaust_a_friendly_unit_(then_attack_with_another)", "SEC_228#0");
            return;
};
