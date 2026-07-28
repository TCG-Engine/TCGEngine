<?php
// SHD_012
// Cost 6 - Bo-Katan Kryze - Princess in Exile - [Heroism,Aggression] - Power 4 - HP 7
// Text: Action [Exhaust]: If you attacked with a Mandalorian unit this phase, deal 1 damage to a unit.
// DeployText: On Attack: You may deal 1 damage to a unit. If you attacked with another Mandalorian unit this phase, you may deal 1 damage to a unit. (The same unit or a different unit.)
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── SHD_012 Bo-Katan Kryze — leader ability DQ handler ──────────────────────
$customDQHandlers["SHD_012#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUAfterAction(intval($player));
};

// ── SHD_012 Bo-Katan Kryze — Leader Unit On Attack ──────────────────────────
// On Attack: You may deal 1 damage to a unit. Then if another Mandalorian attacked this
// phase, you may deal 1 more. Two single MZMAYCHOOSE popups. Declining the first ('-')
// skips the whole ability — this preserves the original YESNO coupling (the first "No"
// aborted before the Mandalorian check), so behaviour is unchanged.
$onAttackAbilities["SHD_012:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $attackerObj = GetZoneObject($mzID);
    $attackerUID = SWUObjUID($attackerObj, 0);

    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', implode('&', $targets), 0,
        'Deal_1_damage_to_a_unit?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', "SHD_012#1|{$player}|{$attackerUID}", 0);
};

$customDQHandlers["SHD_012#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return; // declined → skip whole ability
    global $playerID;
    $playerID = intval($player);
    $attackerUID = intval($parts[1] ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));

    // Second "deal 1" available only if another Mandalorian (uid != $attackerUID) attacked.
    if (!SWUAnotherMandalorianAttacked(intval($player), $attackerUID)) return;

    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', implode('&', $targets), 0,
        'Another_Mandalorian_attacked:_deal_1_more_damage_to_a_unit?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'DEAL_UNIT_DAMAGE|1', 0);
};

// SHD_012 Bo-Katan Kryze — Leader Action [Exhaust]:
// If a Mandalorian unit attacked this phase, deal 1 damage to a unit.
$leaderAbilities["SHD_012"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    $prefix = 'SWU_ATTACKED_MANDALORIAN_';
    $anyMandalorian = false;
    $zone = &GetGlobalEffects($player);
    foreach ($zone as $ge) {
        if (str_starts_with($ge->CardID, $prefix)) { $anyMandalorian = true; break; }
    }

    if (!$anyMandalorian) {
        SWUAfterAction($player);
        return;
    }

    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) {
        SWUAfterAction($player);
        return;
    }
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_unit_to_deal_1_damage');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SHD_012#0', 1);
};
