<?php
// JTL_008
// Cost 5 - Wedge Antilles - Leader of Red Squadron - [Command,Heroism] - Power 3 - HP 6 - Upgrade Power 3 - Upgrade HP 4
// Text: Action [Exhaust]: Play a card from your hand using Piloting. It costs 1 resource less.
// DeployText: / Attached unit is a leader unit. It gains: "On Attack: The next Pilot card you play this phase costs 1 resource less. (This includes Piloting costs.)" /
// Epic Action: If you control 5 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// JTL_008 Wedge Antilles — pilot grant: "On Attack: The next Pilot card you play this phase costs 1
// resource less. (This includes Piloting costs.)" Arms the one-shot SWU_PILOT_DISCOUNT flag, honored
// at BOTH SWUComputePilotCost (attach) and SWUComputePlayCost (a Pilot played as a unit).
$onAttackAbilities["JTL_008:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $atk = GetZoneObject($mzID);
    if ($atk !== null && ($atk->CardID ?? '') === 'JTL_008') return; // pilot-only grant
    AddGlobalEffects(intval($player), 'SWU_PILOT_DISCOUNT');
};

// ── JTL_008 Wedge Antilles (leader action continuation) ─────────────────────
// $lastDecision = the chosen hand card. Initiate the Piloting play (the SWU_PILOT_DISCOUNT flag set in
// the leader ability is honored by SWUComputePilotCost and consumed at charge). SWUQueuePilotVehiclePick
// owns the vehicle pick + attach + after-action; on a fizzle remove the lingering discount flag.
$customDQHandlers["JTL_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) {
        RemoveGlobalEffect(intval($player), 'SWU_PILOT_DISCOUNT');
        SWUAfterAction(intval($player));
        return;
    }
    $o = GetZoneObject($lastDecision);
    if ($o === null || empty($o->CardID)) {
        RemoveGlobalEffect(intval($player), 'SWU_PILOT_DISCOUNT');
        SWUAfterAction(intval($player));
        return;
    }
    $cardID = $o->CardID;
    $vehicles = SWUGetPilotValidTargets(intval($player), $cardID);
    if (empty($vehicles)) {
        RemoveGlobalEffect(intval($player), 'SWU_PILOT_DISCOUNT');
        SWUAfterAction(intval($player));
        return;
    }
    SWUQueuePilotVehiclePick(intval($player), $lastDecision, $cardID, $vehicles);
};

// JTL_008 Wedge Antilles — Leader Action [Exhaust]: Play a card from your hand using Piloting. It costs
// 1 resource less. Set the one-shot SWU_PILOT_DISCOUNT flag (honored at SWUComputePilotCost, so both
// affordability and the charge reflect −1), then offer the hand cards playable via Piloting. The flag
// is consumed at charge time (_SWUFinalizeUpgradeAttach); on a fizzle it is removed here.
$leaderAbilities["JTL_008"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    AddGlobalEffects($player, 'SWU_PILOT_DISCOUNT');
    $targets = [];
    foreach (SWUComputePilotPlayableHand($player) as $idx) {
        $targets[] = "myHand-" . $idx;
    }
    if (empty($targets)) {
        RemoveGlobalEffect($player, 'SWU_PILOT_DISCOUNT');
        SWUAfterAction($player);
        return;
    }
    SWUQueueChooseTarget($player, $targets, "Play_a_card_using_Piloting_(costs_1_less)", "JTL_008#0");
};
