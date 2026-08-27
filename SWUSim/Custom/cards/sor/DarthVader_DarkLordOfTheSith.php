<?php
// SOR_010
// Cost 7 - Darth Vader - Dark Lord of the Sith - [Aggression,Villainy] - Power 5 - HP 8
// Text: Action [1 resource, exhaust]: If you played a [Villainy] card this phase, deal 1 damage to a unit and 1 damage to a base.
// DeployText: On Attack: You may deal 2 damage to a unit.
// Epic Action: If you control 7 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)

// ── SOR_010 Darth Vader — leader ability DQ handler ─────────────────────────
$customDQHandlers["SOR_010#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    // ⚠ "and 1 damage to A BASE" is UNQUALIFIED — it names no controller, so ANY base is a legal target,
    // including YOUR OWN. This dealt to GetOpponent() unconditionally, which was wrong on two counts:
    //   • it removed a legal choice even in Premier (the unqualified-target family — the same reading the
    //     unit half above already uses, since $targets there spans my+their arenas); and
    //   • GetOpponent() names ONE seat and is null above seat 2.
    // SWUAllBaseMzIDs(…, 'any') is myBase-0 plus every theirBase, fanned out across live opponents.
    // ⚠ SWUAfterAction MOVES to the continuation: the action must not close before the damage lands.
    SWUOfferBaseTarget(intval($player), [
        'baseSide' => 'any', 'amount' => 1,
        'continuation' => 'SOR_010#1',
        'prompt' => 'Choose_a_base_to_deal_1_damage',
    ]);
};

// The base half of the leader Action. Closes the action here, not in #0.
$customDQHandlers["SOR_010#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        SWUDealDamageToBase(1, SWUMzOwner((string)$lastDecision, intval($player)), intval($player));
    }
    SWUAfterAction(intval($player));
};

// ── SOR_010 Darth Vader — Leader Unit On Attack ──────────────────────────────
// On Attack: You may deal 2 damage to a unit.
// Single MZMAYCHOOSE: the player picks a target OR declines (lastDecision '-'), which
// DEAL_UNIT_DAMAGE no-ops on. Replaces the old YESNO + re-collect + MZCHOOSE chain.
$onAttackAbilities["SOR_010:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', implode('&', $targets), 0,
        'Deal_2_damage_to_a_unit?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'DEAL_UNIT_DAMAGE|2', 0);
};

// SOR_010 Darth Vader — Leader Action [1 resource, Exhaust]:
// If you played a Villainy card this phase, deal 1 damage to a unit and 1 to an enemy base.
$leaderAbilities["SOR_010"] = function(int $player): void {
    global $playerID;
    $playerID = $player;


    if (GlobalEffectCount($player, 'SWU_PLAYED_VILLAINY') <= 0) {
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_010#0', 1);
};
