<?php
// TWI_012
// Cost 6 - Anakin Skywalker - What it Takes to Win - [Aggression,Heroism] - Power 4 - HP 7
// Text: Action [Exhaust, deal 2 damage to your base]: Attack with a unit. If it's attacking a unit, it gets +2/+0 for this attack.
// DeployText: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / This unit gets +1/+0 for every 5 damage on your base.
// Epic Action: If you control 6 or more resources, deploy this leader.

// TWI_012 Anakin (front continuation) — mark the attacker so it gets +2/+0 if attacking a UNIT, then attack.
$customDQHandlers["TWI_012#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect($lastDecision, 'TWI_012_ATK'); // read in ExecuteSWUAttack: +2/+0 vs a unit target
    BeginSWUAttack(intval($player), $lastDecision);
};

// TWI_012 Anakin Skywalker (front) — "Action [Exhaust, deal 2 damage to your base]: Attack with a unit.
// If it's attacking a unit, it gets +2/+0 for this attack."
$leaderAbilities["TWI_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    SWUDealDamageToBase(2, $player, $player); // additional cost
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit_(+2/+0_if_attacking_a_unit)", "TWI_012#0");
};
