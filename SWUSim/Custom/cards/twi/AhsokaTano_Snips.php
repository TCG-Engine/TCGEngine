<?php
// TWI_011
// Cost 5 - Ahsoka Tano - Snips - [Aggression,Heroism] - Power 3 - HP 6
// Text: Coordinate - Action [Exhaust]: Attack with a unit. It gets +1/+0 for this attack. (Gain this ability while you control 3 or more units.)
// DeployText: Coordinate - This unit gets +2/+0.
// Epic Action: If you control 5 or more resources, deploy this leader. (Flip her, ready her, and move her to the ground arena.)

// TWI_011 Ahsoka Tano (front continuation) — the chosen unit gets +1/+0 for this attack, then attacks.
$customDQHandlers["TWI_011#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    SWUAddAttackPowerBonus($lastDecision, 1);
    BeginSWUAttack(intval($player), $lastDecision);
};

// TWI_011 Ahsoka Tano (front) — "Coordinate - Action [Exhaust]: Attack with a unit. It gets +1/+0 for
// this attack." (Coordinate gated in affordability.)
$leaderAbilities["TWI_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit_(+1/+0)", "TWI_011#0");
};
