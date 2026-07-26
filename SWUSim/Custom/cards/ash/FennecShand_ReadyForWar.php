<?php
// ASH_002
// Cost 4 - Fennec Shand - Ready for War - [Aggression,Cunning] - Power 3 - HP 4
// Text: Action [1 resource, Exhaust, exhaust a friendly unit]: Play a unit from your hand (paying its cost). It enters play ready.
// DeployText: Saboteur / Action [1 resource, exhaust a friendly unit]: Play a unit from your hand. It enters play ready.
// Epic Action: If you control 4 or more resources, deploy this leader.

$customDQHandlers["ASH_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && str_contains($lastDecision, '-')) {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) $o->Status = 0;   // exhaust the friendly unit (cost)
    }
    $handUnits = [];
    foreach (ZoneSearch("myHand", ["Unit", "Token Unit"]) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $handUnits[] = $mz;
    }
    if (empty($handUnits)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $handUnits, "Play_a_unit_from_your_hand_(it_enters_ready)", "ASH_002#1");
};

$customDQHandlers["ASH_002#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gForceEnterReady; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    $gForceEnterReady = true;
    ActivateCard(intval($player), $lastDecision, false);   // play from hand, paying its cost
    $gForceEnterReady = null;
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    SWUAfterAction($player);
};

$unitActionCostKind["ASH_002"] = 'none';
