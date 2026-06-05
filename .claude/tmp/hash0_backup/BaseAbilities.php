<?php
global $baseAbilities;
$baseAbilities = [];

// SOR_022 Energy Conversion Lab — Epic Action: Play a unit costing ≤6 from hand; give it AMBUSH.
// Eligibility uses printed cost (no modifiers per official ruling). Payment is normal (printed + aspect penalty).
// AMBUSH is injected via $gPendingEntryEffects keyed by UniqueID before ActivateCard checks keywords.
$baseAbilities["SOR_022"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $handUnits = ZoneSearch("myHand", ["Unit"]);
    $eligible  = array_values(array_filter($handUnits, function($mzID) {
        $obj = GetZoneObject($mzID);
        return $obj !== null && intval(CardCost($obj->CardID)) <= 6;
    }));
    $playerID = $savedPID;
    if (empty($eligible)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $eligible);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Choose_a_unit_costing_6_or_less");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_022", 1);
};

// SOR_019 Security Complex — Epic Action: Give a Shield token to a non-leader unit.
// ZoneSearch with type ["Unit"] already excludes deployed leaders (CardType="Leader").
// SOR_028 Jedha City — Epic Action: Give a non-leader unit -4/-0 for this phase.
$baseAbilities["SOR_028"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $targets = array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    );
    $playerID = $savedPID;
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_non-leader_unit_-4/-0_for_this_phase", "APPLY_PHASE_DEBUFF|4|0|SOR_028");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// SOR_025 Tarkintown — Epic Action: Deal 3 damage to a damaged non-leader unit.
// ["Unit","Token Unit"] excludes deployed leaders; filter to Damage > 0.
$baseAbilities["SOR_025"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->Damage ?? 0) > 0) $targets[] = $mz;
    }
    $playerID = $savedPID;
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_3_to_a_damaged_non-leader_unit", "DEAL_UNIT_DAMAGE|3");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

$baseAbilities["SOR_019"] = function($player) {
    $targets = array_merge(
        ZoneSearch("myGroundArena", ["Unit"]),
        ZoneSearch("theirGroundArena", ["Unit"]),
        ZoneSearch("mySpaceArena", ["Unit"]),
        ZoneSearch("theirSpaceArena", ["Unit"])
    );
    if (empty($targets)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Choose_a_non-leader_unit_to_shield");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_019", 1);
};
