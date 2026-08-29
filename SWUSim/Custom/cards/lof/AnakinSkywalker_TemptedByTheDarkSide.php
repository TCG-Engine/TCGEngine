<?php
// LOF_018
// Cost 5 - Anakin Skywalker - Tempted by the Dark Side - [Heroism] - Power 4 - HP 6
// Text: Action [Exhaust, use the Force (lose your Force token)]: Play a Villainy non-unit card from your hand, ignoring its aspect penalties.
// DeployText: Action [use the Force]: Play a Villainy non-unit card from your hand, ignoring its aspect penalties.
// Epic Action: If you control 5 or more resources, deploy this leader.

$customDQHandlers["LOF_018#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $cid = $o->CardID;
    // A chosen Villainy Pilot is played AS A PILOT (upgrade) on a friendly Vehicle, ignoring its aspect
    // penalties. Cancel the Piloting aspect surcharge by pre-loading SWU_PILOT_DISCOUNT with the penalty
    // amount (SWUComputePilotCost = base + penalty − discount = base), then hand off to the vehicle pick
    // (which owns the attach, charge, and after-action).
    if (CardType($cid) === 'Unit' && HasKeyword_Piloting($o)) {
        $vehicles = SWUGetPilotValidTargets(intval($player), $cid);
        if (empty($vehicles)) { SWUAfterAction(intval($player)); return; }
        $penalty = SWUAspectPenalty(intval($player), $cid);
        for ($k = 0; $k < $penalty; $k++) AddGlobalEffects(intval($player), 'SWU_PILOT_DISCOUNT');
        SWUQueuePilotVehiclePick(intval($player), $lastDecision, $cid, $vehicles);
        return;
    }
    $penalty = SWUAspectPenalty(intval($player), $cid);  // discount cancels the off-aspect surcharge
    SWUNestedPlay(intval($player), $lastDecision, false, $penalty);
    SWUAfterAction(intval($player));
};

$unitActionCostKind["LOF_018"] = 'none';
