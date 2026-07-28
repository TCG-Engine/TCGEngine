<?php
// TWI_014
// Cost 4 - Asajj Ventress - Unparalleled Adversary - [Cunning,Villainy] - Power 3 - HP 4
// Text: Action [Exhaust]: Attack with a unit. If you played an event this phase, it gets +1/+0 for this attack.
// DeployText: On Attack: If you played an event this phase, this unit gets +1/+0 for this attack and deals combat damage before the defender. (If the defender is defeated, it deals no combat damage.)
// Epic Action: If you control 4 or more resources, deploy this leader.

// TWI_014 Asajj Ventress (front continuation) — attack; +1/+0 if you played an event this phase.
$customDQHandlers["TWI_014#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_EVENT') > 0) SWUAddAttackPowerBonus($lastDecision, 1);
    BeginSWUAttack(intval($player), $lastDecision);
};

// TWI_014 Asajj Ventress (deployed) — "On Attack: If you played an event this phase, this unit gets +1/+0
// for this attack and deals combat damage before the defender."
$onAttackAbilities["TWI_014:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_EVENT') <= 0) return;
    SWUAddAttackPowerBonus($mzID, 1);
    AddTurnEffect($mzID, 'SHOOT_FIRST'); // deals combat damage before the defender
    // Combat owns the after-action.
};

// TWI_014 Asajj Ventress (front) — "Action [Exhaust]: Attack with a unit. If you played an event this
// phase, it gets +1/+0 for this attack."
$leaderAbilities["TWI_014"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit", "TWI_014#0");
};
