<?php
// LOF_014
// Cost 5 - Grand Inquisitor - Stories Travel Quickly - [Cunning,Villainy] - Power 3 - HP 5
// Text: Action [Exhaust, use the Force (lose your Force token)]: Attack with a friendly unit. The defender gets -2/-0 for this attack.
// DeployText: Shielded (When you deploy this leader, give a Shield token to him.) / On Attack: The defender gets -2/-0 for this attack.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LOF_014 Grand Inquisitor — On Attack: The defender gets -2/-0 for this attack. The debuff must land
// BEFORE combat damage, so it's applied synchronously in ExecuteSWUAttack (CombatLogic); this stub-handler
// is a deliberate no-op (the deferred OnAttack window would fire after SWUCombatDamage reads the marker).
$onAttackAbilities["LOF_014:0"] = function($player, $mzID) { /* effect applied synchronously in ExecuteSWUAttack */ };

// LOF_014 Grand Inquisitor — Action [Exhaust, use the Force]: Attack with a friendly unit. The defender
// gets -2/-0 for this attack (one-shot SWU_DEF_DEBUFF_2 on the attacker, read by SWUCombatDamage).
$leaderAbilities["LOF_014"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status ?? 0) === 1) $units[] = "{$zone}-{$i}";
        }
    }
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $units, "Attack_with_a_friendly_unit_(defender_-2/-0)", "LOF_014#0");
};

$customDQHandlers["LOF_014#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect($lastDecision, 'SWU_DEF_DEBUFF_2');
    BeginSWUAttack(intval($player), $lastDecision); // owns the after-action once it attacks
};
