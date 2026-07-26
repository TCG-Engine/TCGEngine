<?php
// JTL_145
// Cost 1 - BB-8 - Happy Beeps - [Aggression,Heroism] - Power 1 - HP 4 - Upgrade Power 1 - Upgrade HP 2
// Text: / Piloting [1 resource Aggression Heroism] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: You may pay 2 resources. If you do, ready a Resistance unit.

// JTL_145 BB-8 (pilot) — When played as an upgrade: You may pay 2 resources. If you do, ready a
// Resistance unit.
$whenPlayedAsUpgradeAbilities["JTL_145:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $resUnits = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && HasTrait($o->CardID ?? '', 'Resistance')) $resUnits[] = $mz;
    }
    if (empty($resUnits) || SWUResourceCount(intval($player), true) < 2) return;
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Pay_2_resources_to_ready_a_Resistance_unit?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_145#0', 1);
};

$customDQHandlers["JTL_145#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 2) return;
    SWUPayCost(intval($player), 2, 0, false);   // effect cost, not halved by JTL_105
    $resUnits = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && HasTrait($o->CardID ?? '', 'Resistance')) $resUnits[] = $mz;
    }
    if (empty($resUnits)) return;
    SWUQueueChooseTarget(intval($player), $resUnits, "Ready_a_Resistance_unit", "READY_UNIT");
};
