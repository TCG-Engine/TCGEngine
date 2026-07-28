<?php
// TWI_009
// Cost 6 - Maul - A Rival in Darkness - [Aggression,Villainy] - Power 6 - HP 6
// Text: Action [Exhaust]: Attack with a unit. It gains Overwhelm for this attack. (When attacking an enemy unit, deal excess damage to the opponent's base.)
// DeployText: Overwhelm / Each other friendly unit gains Overwhelm.
// Epic Action: If you control 6 or more resources, deploy this leader.

// TWI_009 Maul (front continuation) — the chosen unit gains Overwhelm for this attack, then attacks.
$customDQHandlers["TWI_009#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK));
    BeginSWUAttack(intval($player), $lastDecision); // owns the after-action
};

// TWI_009 Maul (front) — "Action [Exhaust]: Attack with a unit. It gains Overwhelm for this attack."
$leaderAbilities["TWI_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit_(gains_Overwhelm)", "TWI_009#0");
};
