<?php
// HMW_009
// Cost 5 - Chewbacca - Relentless Rebel - [Command,Heroism] - Power 3 - HP 6 - Ground - Rebel, Wookiee
// Text: Action [2 resources, Exhaust]: Attack with a unit, even if it's exhausted. It can't attack bases
//       for this attack.
// DeployText: Action: Attack with a unit, even if it's exhausted. It can't attack bases for this attack.
//             Use this ability only once each round.
// Epic Action: If you control 5 or more resources, deploy this leader.
//
// Both sides grant the SAME attack, so they share one continuation and one attacker-collection helper
// (_SWUHmw009Attackers in GameLogic.php — every friendly unit regardless of ready/exhausted, filtered to
// those with a valid NON-BASE target). They differ only in cost: the front pays [2 resources, Exhaust],
// the deployed side is free but limited to once each round (the leader unit's NumUses).

// Shared continuation — attack with the chosen unit; noBases = true is the "can't attack bases" half, and
// BeginSWUAttack has no ready requirement, which is the "even if it's exhausted" half. Combat owns the
// After Action once it actually attacks, so SWUAfterAction is called only on the decline/stale branches.
$customDQHandlers["HMW_009#0"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    if (SWUObjGone(GetZoneObject($lastDecision))) { SWUAfterAction(intval($player)); return; }
    BeginSWUAttack(intval($player), $lastDecision, true);
};

// FRONT (undeployed) — "Action [2 resources, Exhaust]: …". SWULeaderAction already gate-checked
// affordability and exhausted the leader, so the closure only pays the resources. Not target-gated: the
// cost changes game state, so the action stays available and simply fizzles with no legal attacker
// (same treatment as TWI_009/TWI_012's "attack with a unit" leaders).
$leaderActionResourceCosts["HMW_009"] = 2;

$leaderAbilities["HMW_009"] = function (int $player): void {
    global $playerID; $playerID = $player;
    $units = _SWUHmw009Attackers($player);
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $units,
        "Attack_with_a_unit_(even_if_exhausted;_it_can't_attack_bases)", 'HMW_009#0');
};

// DEPLOYED — "Action: … Use this ability only once each round." costKind 'none': no exhaust cost, so the
// leader unit neither needs to be ready nor becomes exhausted (it may well be attacking with something
// else). The once-each-round budget is the leader unit's NumUses, refreshed for every card at
// RegroupPhaseStart (SWUResetAllNumUses) — no bespoke SWU_*_USED flag. Availability (the use being left
// AND a legal attacker existing) is gated in SWUUnitActionAffordable, so activating always does something.
$unitActionCostKind["HMW_009"] = 'none';

$unitAbilities["HMW_009"] = function ($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self) || !SWUHasUseAvailable($self)) { SWUAfterAction(intval($player)); return; }
    SWUConsumeUse($self);
    $units = _SWUHmw009Attackers(intval($player));
    if (empty($units)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $units,
        "Attack_with_a_unit_(even_if_exhausted;_it_can't_attack_bases)", 'HMW_009#0');
};
