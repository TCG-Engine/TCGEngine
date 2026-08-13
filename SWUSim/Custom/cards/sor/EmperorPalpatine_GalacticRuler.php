<?php
// ⚠ Target pools use NonLeaderUnitFilter (Unit + TOKEN Unit): the printed text says "non-leader
// unit", and a bare ["Unit"] filter wrongly excluded TOKEN units too — "take control of a damaged NON-LEADER unit" — a damaged TOKEN is a legal steal
// (the Open Fire filter-family sweep, 2026-08-13).
// SOR_006
// Cost 8 - Emperor Palpatine - Galactic Ruler - [Command,Villainy] - Power 4 - HP 10
// Text: Action [1 resource, exhaust, defeat a friendly unit]: Deal 1 damage to a unit and draw a card.
// DeployText: When Deployed: Take control of a damaged non-leader unit. / On Attack: You may defeat another friendly unit. If you do, deal 1 damage to a unit and draw a card.
// Epic Action: If you control 8 or more resources, deploy this leader.

// ── SOR_006 Emperor Palpatine — Leader front-side ability ────────────────────
// Entry: $leaderAbilities["SOR_006"] (LeaderAbilities.php) — Action [1 resource,
// Exhaust, Defeat a friendly unit]: deal 1 damage to a unit and draw a card.
//   (plain): defeat the sacrifice, then deal 1 to a chosen unit
//   #1: draw
$customDQHandlers["SOR_006#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) {
        DoDrawCard(intval($player), 1);
        SWUAfterAction(intval($player));
        return;
    }
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_unit_to_deal_1_damage_to');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'DEAL_UNIT_DAMAGE|1', 1);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_006#1', 1);
};

$customDQHandlers["SOR_006#1"] = function($player, $parts, $lastDecision) {
    DoDrawCard(intval($player), 1);
    SWUAfterAction(intval($player));
};

// ── SOR_006 Emperor Palpatine — WhenDeployed ─────────────────────────────────
// "When Deployed: Take control of a damaged non-leader unit." (Permanent steal.)
//   #2: take control of the chosen unit
$whenPlayedAbilities["SOR_006:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    ) as $mz) {
        $obj = GetZoneObject($mz);
        if ($obj === null || ($obj->removed ?? false)) continue;
        if (intval($obj->Damage ?? 0) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_damaged_non-leader_unit_to_take_control_of');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_006#2', 1);
};

$customDQHandlers["SOR_006#2"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-') return;
    global $playerID;
    $playerID = intval($player);
    SWUTakeControlOfUnit(intval($player), $lastDecision);
};

// ── SOR_006 Emperor Palpatine — On Attack ────────────────────────────────────
// "On Attack: You may defeat another friendly unit. If you do, deal 1 damage to
// a unit and draw a card."
//   #3: choose the friendly unit to sacrifice
//   #4: defeat it, then deal 1 to a chosen unit
//   #5: draw
$onAttackAbilities["SOR_006:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $friendlies = array_values(array_filter(SWUAllUnits('my'), fn($mz) => $mz !== $mzID));
    if (empty($friendlies)) return; // no unit to sacrifice, skip
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 0,
        'Defeat_another_friendly_unit_for_effect?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', "SOR_006#3|{$mzID}", 0);
};

$customDQHandlers["SOR_006#3"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID;
    $playerID = intval($player);
    $attackerMzID = $parts[0] ?? '';
    $targets = array_values(array_filter(SWUAllUnits('my'), fn($mz) => $mz !== $attackerMzID));
    if (empty($targets)) return;
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 0);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 0,
            'Choose_a_friendly_unit_to_sacrifice');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_006#4', 0);
};

$customDQHandlers["SOR_006#4"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) {
        DoDrawCard(intval($player), 1);
        return;
    }
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 0);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 0,
            'Choose_a_unit_to_deal_1_damage_to');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'DEAL_UNIT_DAMAGE|1', 0);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_006#5', 0);
};

$customDQHandlers["SOR_006#5"] = function($player, $parts, $lastDecision) {
    DoDrawCard(intval($player), 1);
};

// SOR_006 Emperor Palpatine — Leader Action [1 resource, Exhaust, Defeat a friendly unit]:
// Deal 1 damage to a unit and draw a card.
$leaderAbilities["SOR_006"] = function(int $player): void {
    global $playerID;
    $playerID = $player;


    $targets = array_values(SWUAllUnits('my'));
    if (empty($targets)) {
        SWUAfterAction($player);
        return;
    }

    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_friendly_unit_to_sacrifice');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_006#0', 1);
};
