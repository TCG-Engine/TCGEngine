<?php

// Registry of per-card leader action-ability handlers.
// Key: CardID (e.g. "SOR_001"). Value: callable($player) that implements the ability.
// Handlers are responsible for calling SWUAfterAction($player) when done.
// Leaders without a handler registered here will still exhaust but do nothing else.
global $leaderAbilities;
$leaderAbilities = [];

// Resource component of each leader action's cost (omitted = 0). Checked by
// SWULeaderActionAffordable BEFORE the leader exhausts — if the cost can't be
// paid, the action never starts and the leader stays ready (CR: all costs of
// an ability must be payable to use it). Handlers still perform the actual
// payment via SWUExhaustResources.
global $leaderActionResourceCosts;
$leaderActionResourceCosts = [
    "SOR_005" => 1, // Luke Skywalker
    "SOR_010" => 1, // Darth Vader
    "SOR_006" => 1, // Emperor Palpatine (also requires a friendly unit to defeat)
    "SOR_016" => 1, // Grand Admiral Thrawn
    "SOR_007" => 1, // Grand Moff Tarkin
    "SOR_013" => 1, // Cassian Andor
    "JTL_013" => 1, // Poe Dameron (flip + attach as Pilot to a 0-pilot friendly Vehicle)
    "JTL_003" => 1, // Lando Calrissian (play a unit from hand; conditional Shield)
    "JTL_007" => 1, // Admiral Holdo (+2/+2 to a Resistance unit)
    "JTL_015" => 1, // Rio Durant (attack with a space unit, +1/+0 + Saboteur)
    "JTL_016" => 1, // Admiral Ackbar (exhaust a non-leader unit → controller creates an X-Wing)
];

// SOR_002 Iden Versio — Leader Action [Exhaust]:
// "If an enemy unit was defeated this phase, heal 1 damage from your base."
// Passive voice: ANY enemy (opponent-controlled) unit defeated this phase qualifies, regardless of
// who caused it — combat, a sacrifice, or a forced self-defeat (Avenger). That is the opponent's
// SWU_FRIENDLY_DEFEATED flag (a unit they controlled left play via defeat), NOT our own
// SWU_ENEMY_DEFEATED ("you defeated an enemy"), which never sets when the opponent defeats their own.
$leaderAbilities["SOR_002"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount(GetOpponent($player), 'SWU_FRIENDLY_DEFEATED') > 0) {
        OnHealBase($player, $player, 1);
    }
    SWUAfterAction($player);
};

// SOR_005 Luke Skywalker — Leader Action [1 resource, Exhaust]:
// Give a shield token to a unit you played this phase with the Heroism aspect.
$leaderAbilities["SOR_005"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    if (!SWUExhaustResources($player, 1)) {
        SWUAfterAction($player);
        return;
    }

    // Collect units played this phase that have the Heroism aspect.
    $zone = &GetGlobalEffects($player);
    $prefix = 'SWU_PLAYED_UNIT_';
    $heroismTargets = [];
    foreach ($zone as $ge) {
        if (!str_starts_with($ge->CardID, $prefix)) continue;
        $uid = intval(substr($ge->CardID, strlen($prefix)));
        // Find the unit in arenas by UniqueID.
        foreach (['myGroundArena', 'mySpaceArena'] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $obj = GetZoneObject($mz);
                if ($obj === null || ($obj->removed ?? false)) continue;
                if (intval($obj->UniqueID) !== $uid) continue;
                if (strpos(CardAspect($obj->CardID) ?? '', 'Heroism') !== false) {
                    $heroismTargets[] = $mz;
                }
            }
        }
    }
    $heroismTargets = array_values(array_unique($heroismTargets));

    if (empty($heroismTargets)) {
        SWUAfterAction($player);
        return;
    }
    if (count($heroismTargets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $heroismTargets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $heroismTargets), 1,
            'Choose_a_Heroism_unit_played_this_phase_to_give_a_shield_to');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_005', 1);
};

// SOR_010 Darth Vader — Leader Action [1 resource, Exhaust]:
// If you played a Villainy card this phase, deal 1 damage to a unit and 1 to an enemy base.
$leaderAbilities["SOR_010"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    if (!SWUExhaustResources($player, 1)) {
        SWUAfterAction($player);
        return;
    }

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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_010', 1);
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SHD_012', 1);
};

// SOR_006 Emperor Palpatine — Leader Action [1 resource, Exhaust, Defeat a friendly unit]:
// Deal 1 damage to a unit and draw a card.
$leaderAbilities["SOR_006"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    if (!SWUExhaustResources($player, 1)) {
        SWUAfterAction($player);
        return;
    }

    $targets = array_values(array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ));
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_006', 1);
};

// SOR_007 Grand Moff Tarkin — Leader Action [1 resource, exhaust]: Give an Experience token
// to an Imperial unit. (Framework exhausts the leader + gates affordability; closure pays the
// resource, like SOR_006.)
$leaderAbilities["SOR_007"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = array_values(array_filter(array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ), fn($mz) => HasTrait(GetZoneObject($mz)->CardID ?? '', 'Imperial')));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, 'Give_an_Experience_token_to_an_Imperial_unit', 'GIVE_EXPERIENCE|1');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// SOR_003 Chewbacca — Leader Action [Exhaust]: Play a unit that costs 3 or less from your hand
// (paying its cost). It gains Sentinel for this phase. The leader exhaust is handled by
// SWULeaderAction; this closure offers the ≤3 affordable hand units. The play + Sentinel grant
// happen in SOR_003 (it owns the end-of-action via ActivateCard, so no SWUAfterAction here on
// the play path — only on the empty-target fizzle).
$leaderAbilities["SOR_003"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && SWUComputePlayCost($player, $o) <= 3) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets,
        'Play_a_unit_costing_3_or_less_(it_gains_Sentinel)', 'SOR_003');
};

// SOR_013 Cassian Andor — Leader Action [1 resource, Exhaust]: If you've dealt 3 or more damage to
// an enemy base this phase, draw a card. The 1-resource affordability is gated in
// SWULeaderActionAffordable; the leader exhausts via SWULeaderAction. The cumulative damage is the
// SWU_BASEDMG_AMT_{opponent} counter (one flag per point), set in SWUDealDamageToBase. Like Iden
// (SOR_002), the action is still spent if the condition fails.
$leaderAbilities["SOR_013"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $opp = OtherPlayer($player);
    if (GlobalEffectCount($player, 'SWU_BASEDMG_AMT_' . $opp) >= 3) {
        DoDrawCard($player, 1);
    }
    SWUAfterAction($player);
};

// SOR_014 Sabine Wren — Leader Action [Exhaust]: Deal 1 damage to each base.
$leaderAbilities["SOR_014"] = function(int $player): void {
    SWUDealDamageToBase(1, 1);
    SWUDealDamageToBase(1, 2);
    SWUAfterAction($player);
};

// SOR_016 Grand Admiral Thrawn — Leader Action [1 resource, Exhaust]:
// Reveal the top card of any player's deck. Exhaust a unit that costs <= that card's cost.
$leaderAbilities["SOR_016"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    if (!SWUExhaustResources($player, 1)) {
        SWUAfterAction($player);
        return;
    }

    DecisionQueueController::AddDecision($player, 'YESNO', '', 1, 'Own_deck_or_opponent?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_016|action', 1);
};

// SOR_017 Han Solo "Audacious Smuggler" — Leader Action [Exhaust]:
// "Put a card from your hand into play as a resource and ready it. At the start of
//  the next action phase, defeat a resource you control."
// Affordability (hand non-empty) is checked in SWULeaderActionAffordable.
$leaderAbilities["SOR_017"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { // safety net — should be gated upstream
        SWUAfterAction($player);
        return;
    }
    if (count($hand) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $hand[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $hand), 1,
            'Choose_a_card_to_put_into_play_as_a_ready_resource');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_017', 1);
};

// SOR_004 Chirrut Îmwe — Leader Action [Exhaust]: Give a unit +0/+2 for this phase.
// "a unit" = any unit (friendly or enemy). 1 target auto-resolves; the buff flows through
// the existing APPLY_PHASE_BUFF handler (SWUBUFF_0_2, cleared at RegroupPhaseStart).
$leaderAbilities["SOR_004"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, 'Give_a_unit_+0/+2_for_this_phase', 'APPLY_PHASE_BUFF|0|2|SOR_004');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// SOR_018 Jyn Erso — Leader Action [Exhaust]: Attack with a unit. The defender gets -1/-0
// for this attack. Choose a friendly READY unit; the SOR_018 handler tags it with a one-shot
// SWU_DEF_DEBUFF_1 (consumed in SWUCombatDamage) and begins the attack.
$leaderAbilities["SOR_018"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $attackers = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }));
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attackers, 'Attack_with_a_unit_(defender_gets_-1/-0)', 'SOR_018');
};

// SOR_009 Leia Organa — Leader Action [Exhaust]: Attack with a Rebel unit. Then, you may attack
// with another Rebel unit. The SOR_009 handler arms the chained "you may attack with another"
// (rebelOnly, may-decline, +0) before the first attack. Deployed side (Raid 1 + OnAttackEnd) lives
// in CardDQHandlers.php / $Raid_Cards.
$leaderAbilities["SOR_009"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $rebels = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1 && HasTrait($o->CardID, 'Rebel'); }));
    if (empty($rebels)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $rebels, 'Attack_with_a_Rebel_unit', 'SOR_009');
};

// SOR_012 IG-88 — Leader Action [Exhaust]: Attack with a unit. If you control more units than
// the defending player, the attacker gets +1/+0 for this attack. (Defending player is always the
// opponent in a 2-player game, so the count condition is resolved in the SOR_012 handler before
// the attack target is even chosen.) Deployed side ("each other friendly unit gains Raid 1") is
// already implemented in KeywordEffects.php.
$leaderAbilities["SOR_012"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $attackers = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }));
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attackers, 'Attack_with_a_unit', 'SOR_012');
};

// SOR_011 Grand Inquisitor — Leader Action [Exhaust]: Deal 2 damage to a friendly unit with
// 3 or less power and ready it. No legal target → fizzle (the leader still pays its exhaust).
$leaderAbilities["SOR_011"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval(ObjectCurrentPower($o)) <= 3; }));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, 'Deal_2_damage_to_a_friendly_unit_(3_or_less_power)_and_ready_it', 'SOR_011');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// TWI_005 Count Dooku — Leader Action [Exhaust]: Play a Separatist card from your hand.
// It gains Exploit 1. Affordability (≥1 affordable Separatist in hand) is checked in
// SWULeaderActionAffordable. The deployed-unit side uses the same TWI_005 handler
// via $unitAbilities["TWI_005"] in CardDQHandlers.php.
$leaderAbilities["TWI_005"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = _SWUSeparatistHandPlayables($player);
    if (empty($targets)) { SWUAfterAction($player); return; } // safety net — gated upstream
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_Separatist_card_to_play_(it_gains_Exploit_1)');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'TWI_005', 1);
};

// JTL_013 Poe Dameron — Leader Action [1 resource, Exhaust]:
// "Flip this leader and attach him as an upgrade to a friendly Vehicle unit without a Pilot on it."
// The handler flips the leader (Deployed = true), pays the resource (already exhausted by
// SWULeaderAction), picks the Vehicle, and attaches via _SWUFinalizeUpgradeAttach (isPilot=true).
// The host does NOT become a Leader Unit — JTL_013 is not in CardLeaderCanDeployAsUpgrade.
// EpicActionUsed is NOT set — this is a leader Action, not the Epic-Action deploy threshold.
// Affordability (≥1 friendly 0-pilot Vehicle, ≥1 ready resource) is gated in SWULeaderActionAffordable.
$leaderAbilities["JTL_013"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    if (!SWUExhaustResources($player, 1)) {
        SWUAfterAction($player);
        return;
    }

    // Flip the leader to its deployed side (but NOT the epic-action threshold deploy).
    $leaderArr = &GetLeader($player);
    for ($i = 0; $i < count($leaderArr); $i++) {
        if (!isset($leaderArr[$i]->removed) || !$leaderArr[$i]->removed) {
            $leaderArr[$i]->Deployed       = true;
            $leaderArr[$i]->DeployedUniqueID = 0; // attached as subcard, no standalone arena UID
            break;
        }
    }

    $vehicles = SWUGetPoe013AttachVehicles($player);
    if (empty($vehicles)) {
        SWUAfterAction($player);
        return;
    }

    if (count($vehicles) === 1) {
        // Auto-attach to the single eligible Vehicle — no picker needed.
        _SWUFinalizeUpgradeAttach($player, 'JTL_013', '', $vehicles[0], 0, true, true);
        return;
    }

    // 2+ eligible Vehicles: let the player pick.
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $vehicles), 1,
        tooltip: 'Choose_a_Vehicle_to_attach_Poe_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_013', 1);
};

// JTL_001 Asajj Ventress — Leader Action [Exhaust]: Deal 1 damage to a friendly unit. If you do, deal
// 1 damage to an enemy unit in the same arena. Mandatory friendly target (no decline); the enemy half
// is gated on an enemy unit existing in the SAME arena as the damaged friendly unit. Continuation in
// CardDQHandlers.php ("JTL_001") deals the friendly damage then offers the same-arena enemy.
$leaderAbilities["JTL_001"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $friendly = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    );
    if (empty($friendly)) { SWUAfterAction($player); return; } // no friendly to damage → fizzle
    SWUQueueChooseTarget($player, $friendly,
        "Deal_1_damage_to_a_friendly_unit", "JTL_001");
};

// JTL_018 Kazuda Xiono — Leader Action [Exhaust]: A friendly unit loses all abilities for this round.
// Take an extra action after this one. The extra action is always granted (even with no friendly unit);
// continuation in CardDQHandlers.php ("JTL_018") applies the lose-abilities token then ends WITHOUT
// swapping the turn player (SWUAfterActionExtra).
$leaderAbilities["JTL_018"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $friendly = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    );
    if (empty($friendly)) { SWUAfterActionExtra($player); return; } // no unit → still take the extra action
    SWUQueueChooseTarget($player, $friendly,
        "A_friendly_unit_loses_all_abilities_this_round", "JTL_018");
};

// JTL_003 Lando Calrissian — Leader Action [1 resource, Exhaust]: Play a unit from your hand (paying
// its cost). If you do and you control a ground unit and a space unit, give a Shield token to a unit.
// The 1-resource is paid here (after the affordability gate); affordability of the played unit is
// against the remaining resources. Continuation in CardDQHandlers.php ("JTL_003" → "JTL_003#1").
$leaderAbilities["JTL_003"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; } // gate should prevent
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no affordable unit → action spent
    SWUQueueChooseTarget($player, $targets, "Play_a_unit_from_your_hand", "JTL_003");
};

// JTL_004 Rose Tico — Leader Action [Exhaust]: Heal 2 damage from a Vehicle unit that attacked this
// phase. "Attacked this phase" = the unit's controller carries its SWU_ATTACKED_{uid} flag (set in
// CombatLogic on every attack). Any Vehicle (friendly or enemy) qualifies. HEAL_TARGET closes nothing,
// so SWU_AFTER_ACTION is queued last.
$leaderAbilities["JTL_004"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (!HasTrait($o->CardID, 'Vehicle')) continue;
        $ctrl = intval($o->Controller ?? 0);
        if (GlobalEffectCount($ctrl, 'SWU_ATTACKED_' . intval($o->UniqueID ?? 0)) <= 0) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no Vehicle attacked → fizzle
    SWUQueueChooseTarget($player, $targets,
        "Heal_2_from_a_Vehicle_that_attacked_this_phase", "HEAL_TARGET|2");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// JTL_005 Admiral Piett — Leader Action [Exhaust]: Play a Capital Ship unit from your hand. It costs 1
// resource less. Filter the hand playables (at the 1-resource discount) to Capital Ship units; the
// continuation ("JTL_005") plays the chosen card at the discount. (The deployed-side –2 passive lives
// in $playCostFieldModifiers in GameLogic.php and only applies while Piett is in the arena.)
$leaderAbilities["JTL_005"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 1) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Capital Ship')) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no Capital Ship → fizzle
    SWUQueueChooseTarget($player, $targets, "Play_a_Capital_Ship_unit_(costs_1_less)", "JTL_005");
};

// JTL_006 Darth Vader — Leader Action [Exhaust]: If you attacked with a non-token Vehicle unit this
// phase, create a TIE Fighter token. The condition is the SWU_ATTACKED_VEHICLE flag (set in CombatLogic
// when a non-token Vehicle attacks). Either way the leader exhausts.
$leaderAbilities["JTL_006"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_ATTACKED_VEHICLE') > 0) {
        SWUCreateUnitToken($player, 'JTL_T01'); // TIE Fighter (Space, 1/1)
    }
    SWUAfterAction($player);
};

// JTL_007 Admiral Holdo — Leader Action [1 resource, Exhaust]: Give a Resistance unit (or a unit with
// a Resistance upgrade on it) +2/+2 for this phase. Target = any qualifying unit (friendly or enemy);
// the +2/+2 flows through APPLY_PHASE_BUFF (registered token JTL_007, expires at regroup).
$leaderAbilities["JTL_007"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        if (_SWUIsResistanceTarget(GetZoneObject($mz))) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no eligible unit → action spent
    SWUQueueChooseTarget($player, $targets,
        "Give_a_Resistance_unit_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_007");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
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
    SWUQueueChooseTarget($player, $targets, "Play_a_card_using_Piloting_(costs_1_less)", "JTL_008");
};

// JTL_010 Captain Phasma — Leader Action [Exhaust]: If you played a First Order card this phase, deal 1
// damage to a base. Condition = SWU_PLAYED_FO flag; the base target is chosen (DEAL_BASE_DAMAGE|1).
$leaderAbilities["JTL_010"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_PLAYED_FO') <= 0) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, ['myBase-0', 'theirBase-0'],
        "Deal_1_damage_to_a_base", "DEAL_BASE_DAMAGE|1");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// JTL_011 Major Vonreg — Leader Action [Exhaust]: Play a Vehicle unit from your hand (paying its cost).
// If you do, give another unit +1/+0 for this phase. Continuation in CardDQHandlers.php ("JTL_011" plays
// the chosen Vehicle then "JTL_011#1" buffs another unit, excluding the just-played one by UniqueID).
$leaderAbilities["JTL_011"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no Vehicle to play → action spent
    SWUQueueChooseTarget($player, $targets, "Play_a_Vehicle_unit_from_your_hand", "JTL_011");
};

// JTL_012 Luke Skywalker — Leader Action [Exhaust]: If you attacked with a Fighter unit this phase,
// deal 1 damage to a unit. Condition = the SWU_ATTACKED_FIGHTER flag (set in CombatLogic).
$leaderAbilities["JTL_012"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_ATTACKED_FIGHTER') <= 0) { SWUAfterAction($player); return; }
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) { SWUAfterAction($player); return; } // no unit to hit → fizzle
    SWUQueueChooseTarget($player, $targets, "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// JTL_014 Admiral Trench — Leader Action [Exhaust]: Discard a card that costs 3 or more from your hand.
// If you do, draw a card. (The deployed-side "When Deployed" reveal/discard/draw ability is handled
// separately.) Mandatory discard if an eligible card exists; the draw rides the JTL_014 continuation.
$leaderAbilities["JTL_014"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (ZoneSearch("myHand") as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) >= 3) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no 3+ cost card → action spent
    SWUQueueChooseTarget($player, $targets, "Discard_a_card_costing_3_or_more", "JTL_014");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// JTL_015 Rio Durant — Leader Action [1 resource, Exhaust]: Attack with a space unit. It gets +1/+0
// and gains Saboteur for this attack. Continuation grants the per-attack effects then BeginSWUAttack.
$leaderAbilities["JTL_015"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $attackers = array_values(array_filter(
        ZoneSearch('mySpaceArena', AnyUnitFilter),
        function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }
    ));
    if (empty($attackers)) { SWUAfterAction($player); return; } // no ready space unit → fizzle
    SWUQueueChooseTarget($player, $attackers,
        "Attack_with_a_space_unit_(+1/+0,_Saboteur_this_attack)", "JTL_015");
};

// JTL_016 Admiral Ackbar — Leader Action [1 resource, Exhaust]: Exhaust a non-leader unit. If you do,
// its controller creates an X-Wing token. The continuation exhausts the chosen unit and gives its
// CONTROLLER (which may be the opponent) an X-Wing.
$leaderAbilities["JTL_016"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    );
    if (empty($targets)) { SWUAfterAction($player); return; } // no non-leader unit → action spent
    SWUQueueChooseTarget($player, $targets,
        "Exhaust_a_non-leader_unit_(its_controller_creates_an_X-Wing)", "JTL_016");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// JTL_017 Han Solo — Leader Action [Exhaust]: Reveal the top card of your deck, then attack with a
// unit. If the revealed card and that unit have different odd costs, that unit gets +1/+0 for this
// attack. Reveal here (read top cost), then choose the attacker; the continuation evaluates the
// odd-cost condition and begins the attack.
$leaderAbilities["JTL_017"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $deck = GetDeck($player);
    $revealedCost = -1;
    foreach ($deck as $c) {
        if (!empty($c->removed)) continue;
        $revealedCost = intval(CardCost($c->CardID));
        AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . GameLogCardRef($c->CardID));
        break;
    }
    $attackers = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }));
    if (empty($attackers)) { SWUAfterAction($player); return; } // no ready unit to attack → fizzle
    SWUQueueChooseTarget($player, $attackers,
        "Attack_with_a_unit_(+1/+0_if_different_odd_costs)", "JTL_017|" . $revealedCost);
};

?>
