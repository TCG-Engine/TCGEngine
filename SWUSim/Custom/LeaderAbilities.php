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
    "LOF_004" => 1, // Kanan Jarrus (Shield to a Creature or Spectre unit)
    "LOF_006" => 1, // Supreme Leader Snoke (Experience to highest-power unit)
    "LOF_011" => 1, // Kit Fisto (if attacked with a Jedi this phase, deal 2)
    "SEC_001" => 1, // Chancellor Palpatine (search top 5 for a Plot card)
    "SEC_002" => 1, // Jabba the Hutt (a friendly damaged unit deals damage to an enemy unit)
    "SEC_004" => 1, // Leia Organa (disclose → give Experience to a non-aspect-sharing unit)
    "SEC_008" => 1, // Bail Organa (return a friendly resource → ramp top of deck)
    "SEC_010" => 1, // Dedra Meero (enemy's controller may self-damage it, else you draw)
    "SEC_011" => 1, // Governor Pryce (ready a token unit)
    "SEC_014" => 1, // Sly Moore (if 4+ exhausted units in play, create a Spy)
    "SEC_015" => 1, // C-3PO (if you control an exhausted unit, exhaust a unit)
    "IBH_001" => 1, // Leia Organa (heal 1 from a friendly unit)
    "IBH_053" => 1, // Darth Vader (deal 1 to a base)
];

// LOF leaders whose Action cost includes "use the Force (lose your Force token)". Gated in
// SWULeaderActionAffordable; the ability closures call UseTheForce() to pay.
global $leaderActionForceCost;
$leaderActionForceCost = [
    "LOF_002" => true, // Mother Talzin
    "LOF_003" => true, // Ahsoka Tano
    "LOF_008" => true, // Obi-Wan Kenobi
    "LOF_009" => true, // Darth Maul
    "LOF_013" => true, // Barriss Offee
    "LOF_014" => true, // Grand Inquisitor
    "LOF_015" => true, // Cal Kestis
    "LOF_016" => true, // Qui-Gon Jinn
    "LOF_018" => true, // Anakin Skywalker
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_005#0', 1);
};

// IBH_001 Leia Organa — Leader Action [1 resource, Exhaust]: heal 1 damage from a friendly unit.
$leaderAbilities["IBH_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Heal_1_from_a_friendly_unit", "HEAL_TARGET|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// IBH_053 Darth Vader — Leader Action [1 resource, Exhaust]: deal 1 damage to a base.
$leaderAbilities["IBH_053"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, ['myBase-0', 'theirBase-0'], "Deal_1_damage_to_a_base", "DEAL_BASE_DAMAGE|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_010#0', 1);
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_006#0', 1);
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
        'Play_a_unit_costing_3_or_less_(it_gains_Sentinel)', 'SOR_003#0');
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_016#0|action', 1);
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_017#0', 1);
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
    SWUQueueChooseTarget($player, $attackers, 'Attack_with_a_unit_(defender_gets_-1/-0)', 'SOR_018#0');
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
    SWUQueueChooseTarget($player, $rebels, 'Attack_with_a_Rebel_unit', 'SOR_009#0');
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
    SWUQueueChooseTarget($player, $attackers, 'Attack_with_a_unit', 'SOR_012#0');
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
    SWUQueueChooseTarget($player, $targets, 'Deal_2_damage_to_a_friendly_unit_(3_or_less_power)_and_ready_it', 'SOR_011#0');
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'TWI_005#0', 1);
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
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_013#0', 1);
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
        "Deal_1_damage_to_a_friendly_unit", "JTL_001#0");
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
        "A_friendly_unit_loses_all_abilities_this_round", "JTL_018#0");
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
    SWUQueueChooseTarget($player, $targets, "Play_a_unit_from_your_hand", "JTL_003#0");
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
    SWUQueueChooseTarget($player, $targets, "Play_a_Capital_Ship_unit_(costs_1_less)", "JTL_005#0");
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
    SWUQueueChooseTarget($player, $targets, "Play_a_card_using_Piloting_(costs_1_less)", "JTL_008#0");
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
    SWUQueueChooseTarget($player, $targets, "Play_a_Vehicle_unit_from_your_hand", "JTL_011#0");
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
    SWUQueueChooseTarget($player, $targets, "Discard_a_card_costing_3_or_more", "JTL_014#0");
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
        "Attack_with_a_space_unit_(+1/+0,_Saboteur_this_attack)", "JTL_015#0");
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
        "Exhaust_a_non-leader_unit_(its_controller_creates_an_X-Wing)", "JTL_016#0");
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
        "Attack_with_a_unit_(+1/+0_if_different_odd_costs)", "JTL_017#0|" . $revealedCost);
};

// ═══════════════════════════════════════════════════════════════════════════
// LOF Leaders — Phase 14 (leader-side Actions)
// ═══════════════════════════════════════════════════════════════════════════

// LOF_001 Kylo Ren — Action [Exhaust]: Discard a card from your hand. If you discarded an upgrade this
// way, draw a card.
$leaderAbilities["LOF_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = GetHand($player); $cards = [];
    for ($i = 0; $i < count($hand); $i++) {
        if ($hand[$i] !== null && empty($hand[$i]->removed)) $cards[] = "myHand-{$i}";
    }
    if (empty($cards)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $cards, "Discard_a_card_(draw_if_it's_an_upgrade)", "LOF_001#0");
};
$customDQHandlers["LOF_001#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            $cardID = $o->CardID; $isUpgrade = (CardType($cardID) === 'Upgrade');
            $o->removed = true; SWUAddToDiscard(intval($player), $cardID, 'HAND');
            DecisionQueueController::CleanupRemovedCards();
            if ($isUpgrade) DoDrawCard(intval($player), 1);
        }
    }
    SWUAfterAction(intval($player));
};

// LOF_002 Mother Talzin — Action [Exhaust, use the Force]: Give a unit -1/-1 for this phase.
$leaderAbilities["LOF_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player); // affordability already confirmed the Force token
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_unit_-1/-1_this_phase", "LOF_002#0");
};
$customDQHandlers["LOF_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUApplyPhaseDebuff($lastDecision, 1, 1, 'LOF_002');
    }
    SWUAfterAction(intval($player));
};

// LOF_003 Ahsoka Tano — Action [Exhaust, use the Force]: Give a friendly unit Sentinel for this phase.
$leaderAbilities["LOF_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_friendly_unit_Sentinel_this_phase", "LOF_003#0");
};
$customDQHandlers["LOF_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        AddTurnEffect($lastDecision, 'SENTINEL');
    }
    SWUAfterAction(intval($player));
};

// LOF_004 Kanan Jarrus — Action [1 resource, Exhaust]: Give a Shield token to a Creature or Spectre unit.
$leaderAbilities["LOF_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                         ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if ($o === null || !empty($o->removed)) continue;
        if (HasTrait($o->CardID ?? '', 'Creature') || HasTrait($o->CardID ?? '', 'Spectre')) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_Shield_to_a_Creature_or_Spectre_unit", "LOF_004#0");
};
$customDQHandlers["LOF_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        DoGiveShieldToken(intval($player), $lastDecision);
    }
    SWUAfterAction(intval($player));
};

// LOF_005 Morgan Elsbeth — Action [Exhaust]: Choose a friendly unit that attacked this phase. Play a unit
// from your hand that shares a keyword with the chosen unit. It costs 1 resource less.
function _SWUCardKeywordSet(string $cardID): array {
    $kws = [];
    foreach (['Ambush','Bounty','Coordinate','Exploit','Grit','Hidden','Overwhelm','Piloting','Plot','Raid','Restore','Saboteur','Sentinel','Shielded','Smuggle'] as $kw) {
        $reg = $GLOBALS[$kw . '_Cards'] ?? null;
        if (is_array($reg) && isset($reg[$cardID])) $kws[] = $kw;
    }
    return $kws;
}
// LOF_005 deployed On Attack discount: does $cardID (a card in hand → printed keywords only) share a
// keyword with any friendly unit IN PLAY (which counts its current printed + conditional + granted keywords)?
function _SWULof005SharesKeywordWithFriendly(int $player, string $cardID): bool {
    $myKw = _SWUCardKeywordSet($cardID);
    if (empty($myKw)) return false;
    foreach (GetUnitsInPlay($player) as $u) {
        if (!empty($u->removed)) continue;
        $uKw = _SWUCardKeywordSet($u->CardID ?? '');
        foreach (['Ambush'=>'AMBUSH','Grit'=>'GRIT','Hidden'=>'HIDDEN','Overwhelm'=>'OVERWHELM','Saboteur'=>'SABOTEUR','Sentinel'=>'SENTINEL','Shielded'=>'SHIELDED','Raid'=>'RAID','Restore'=>'RESTORE'] as $name => $kw) {
            if (!in_array($name, $uKw, true) && _SWUUnitHasKeyword($u, $kw)) $uKw[] = $name;
        }
        if (!empty(array_intersect($myKw, $uKw))) return true;
    }
    return false;
}
$leaderAbilities["LOF_005"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $attacked = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (GlobalEffectCount($player, 'SWU_ATTACKED_' . intval($o->UniqueID ?? -1)) > 0) $attacked[] = $mz;
    }
    if (empty($attacked)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attacked, "Choose_a_friendly_unit_that_attacked_this_phase", "LOF_005#0");
};
$customDQHandlers["LOF_005#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $chosen = GetZoneObject($lastDecision);
    if ($chosen === null || !empty($chosen->removed)) { SWUAfterAction(intval($player)); return; }
    // The chosen unit is IN PLAY, so it counts its CURRENT keywords (printed + conditional + granted). The
    // hand candidates count PRINTED keywords only (cards don't have abilities / conditional keywords in hand).
    $chosenKw = _SWUCardKeywordSet($chosen->CardID ?? '');
    foreach (['Ambush'=>'AMBUSH','Grit'=>'GRIT','Hidden'=>'HIDDEN','Overwhelm'=>'OVERWHELM','Saboteur'=>'SABOTEUR','Sentinel'=>'SENTINEL','Shielded'=>'SHIELDED','Raid'=>'RAID','Restore'=>'RESTORE'] as $name => $kw) {
        if (!in_array($name, $chosenKw, true) && _SWUUnitHasKeyword($chosen, $kw)) $chosenKw[] = $name;
    }
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 1) as $mz) {
        $h = GetZoneObject($mz);
        if ($h === null || !empty($h->removed)) continue;
        if (!empty(array_intersect($chosenKw, _SWUCardKeywordSet($h->CardID ?? '')))) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_sharing_a_keyword_(it_costs_1_less)", "DISCOUNT_PLAY_FROM_HAND|1");
};

// LOF_006 Supreme Leader Snoke — Action [1 resource, Exhaust]: Give an Experience token to the unit with
// the most power among friendly Villainy units. (Choose one if tied.)
$leaderAbilities["LOF_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $villainy = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if ($o === null || !empty($o->removed)) continue;
        if (strpos(CardAspect($o->CardID ?? '') ?? '', 'Villainy') !== false) $villainy[] = $mz;
    }
    if (empty($villainy)) { SWUAfterAction($player); return; }
    $maxP = -1;
    foreach ($villainy as $mz) { $p = intval(ObjectCurrentPower(GetZoneObject($mz))); if ($p > $maxP) $maxP = $p; }
    $top = array_values(array_filter($villainy, fn($mz) => intval(ObjectCurrentPower(GetZoneObject($mz))) === $maxP));
    if (count($top) === 1) {
        DoGiveExperienceToken($player, $top[0]);
        SWUAfterAction($player);
        return;
    }
    SWUQueueChooseTarget($player, $top, "Choose_a_tied_Villainy_unit_for_an_Experience_token", "LOF_006#0");
};
$customDQHandlers["LOF_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') DoGiveExperienceToken(intval($player), $lastDecision);
    SWUAfterAction(intval($player));
};

// LOF_007 Avar Kriss — Action [Exhaust]: The Force is with you (create your Force token). (Her Epic Action
// conditional deploy is gated in SWUDeployLeader: resources + Force-uses-this-phase ≥ 9.)
$leaderAbilities["LOF_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    TheForceIsWithYou($player);
    SWUAfterAction($player);
};

// LOF_008 Obi-Wan Kenobi — Action [Exhaust, use the Force]: Give an Experience token to a unit without an
// Experience token on it.
$leaderAbilities["LOF_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                         ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if ($o === null || !empty($o->removed)) continue;
        $hasExp = false;
        foreach (($o->Subcards ?? []) as $sc) { if (($sc->CardID ?? '') === 'SOR_T01') { $hasExp = true; break; } }
        if (!$hasExp) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_without_one", "LOF_008#0");
};
$customDQHandlers["LOF_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') DoGiveExperienceToken(intval($player), $lastDecision);
    SWUAfterAction(intval($player));
};

// LOF_009 Darth Maul — Action [Exhaust, use the Force]: Deal 1 damage to a unit and 1 damage to a
// different unit.
$leaderAbilities["LOF_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_damage_to_a_unit", "LOF_009#0");
};
$customDQHandlers["LOF_009#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $firstUID = intval(GetZoneObject($lastDecision)->UniqueID ?? -1);
    // Second target: a DIFFERENT unit.
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                         ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed) || intval($o->UniqueID ?? -1) === $firstUID) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_different_unit", "LOF_009#1");
};
$customDQHandlers["LOF_009#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUAfterAction(intval($player));
};

// LOF_010 Third Sister — Action [Exhaust]: Play a unit from your hand. It gains Hidden for this phase.
$leaderAbilities["LOF_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_a_unit_from_hand_(it_gains_Hidden)", "LOF_010#0");
};
$customDQHandlers["LOF_010#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    $gPlayGrantTurnEffect = 'HIDDEN';
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gPlayGrantTurnEffect = null;
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    SWUAfterAction(intval($player));
};

// LOF_011 Kit Fisto — Action [1 resource, Exhaust]: If you attacked with a Jedi unit this phase, deal 2
// damage to a unit.
$leaderAbilities["LOF_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    if (GlobalEffectCount($player, 'SWU_ATTACKED_JEDI') <= 0) { SWUAfterAction($player); return; }
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_2_damage_to_a_unit", "LOF_011#0");
};
$customDQHandlers["LOF_011#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 2, intval($player));
    SWUAfterAction(intval($player));
};

// LOF_012 Rey — Action [Exhaust]: If you played a non-unit Force card this phase, deal 1 damage to a unit.
$leaderAbilities["LOF_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_PLAYED_NONUNIT_FORCE') <= 0) { SWUAfterAction($player); return; }
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_damage_to_a_unit", "LOF_012#0");
};
$customDQHandlers["LOF_012#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUAfterAction(intval($player));
};

// LOF_013 Barriss Offee — Action [Exhaust, use the Force]: Play an event from your hand. It costs 1 less.
// DISCOUNT_PLAY_FROM_HAND owns the after-action (ActivateCard on play, SWUAfterAction on decline).
$leaderAbilities["LOF_013"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = SWUHandPlayablesAtDiscount($player, ['Event'], 1);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_an_event_from_hand_(costs_1_less)", "DISCOUNT_PLAY_FROM_HAND|1");
};

// LOF_014 Grand Inquisitor — Action [Exhaust, use the Force]: Attack with a friendly unit. The defender
// gets -2/-0 for this attack (one-shot SWU_DEF_DEBUFF_2 on the attacker, read by SWUCombatDamage).
$leaderAbilities["LOF_014"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if ($u === null || !empty($u->removed)) continue;
            if (intval($u->Status ?? 0) === 1) $units[] = "{$zone}-{$i}";
        }
    }
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $units, "Attack_with_a_friendly_unit_(defender_-2/-0)", "LOF_014#0");
};
$customDQHandlers["LOF_014#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect($lastDecision, 'SWU_DEF_DEBUFF_2');
    BeginSWUAttack(intval($player), $lastDecision); // owns the after-action once it attacks
};

// LOF_015 Cal Kestis — Action [Exhaust, use the Force]: An opponent chooses a ready unit they control.
// Exhaust that unit. The opponent's choice is queued via an intermediate CUSTOM (LOF_015_OPP) so it
// survives SWULeaderAction's $playerID restore; the chain owns the leader after-action in every branch.
$leaderAbilities["LOF_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_015_OPP|{$player}", 1);
};
$customDQHandlers["LOF_015_OPP"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $opp = OtherPlayer($caster);
    $playerID = $opp;
    $units = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->Status ?? 0) === 1) $units[] = $mz; // ready units only
    }
    if (empty($units)) { $playerID = $caster; SWUAfterAction($caster); return; }
    if (count($units) === 1) {
        $o = GetZoneObject($units[0]);
        if ($o !== null && empty($o->removed)) $o->Status = 0; // exhaust
        $playerID = $caster; SWUAfterAction($caster); return;
    }
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $units), 1, tooltip: "Choose_a_ready_unit_to_exhaust");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LOF_015_EXHAUST|{$caster}", 1);
    // leave $playerID = $opp so MZCountChoices resolves the relative mzIDs under the opponent
};
$customDQHandlers["LOF_015_EXHAUST"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $playerID = intval($player); // opponent frame to resolve their relative mzID
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) $o->Status = 0; // exhaust
    }
    $playerID = $caster;
    SWUAfterAction($caster);
};

// LOF_016 Qui-Gon Jinn — Action [Exhaust, use the Force]: Return a friendly non-leader unit to its owner's
// hand. Play a non-Villainy unit that costs less than the returned unit from your hand for free.
$leaderAbilities["LOF_016"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Return_a_friendly_non-leader_unit_to_hand", "LOF_016#0");
};
$customDQHandlers["LOF_016#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $returnedCost = intval(CardCost($o->CardID));
    SWUBounceUnit(intval($player), $lastDecision);
    $playables = [];
    foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 999) as $mz) { // free play → ignore affordability
        $h = GetZoneObject($mz); if ($h === null || !empty($h->removed)) continue;
        if (intval(CardCost($h->CardID)) < $returnedCost && strpos(CardAspect($h->CardID) ?? '', 'Villainy') === false) $playables[] = $mz;
    }
    if (empty($playables)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $playables, "Play_a_cheaper_non-Villainy_unit_for_free", "LOF_016#1");
};
$customDQHandlers["LOF_016#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, true, 0); // ignoreCost = true (free)
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    SWUAfterAction(intval($player));
};

// LOF_018 Anakin Skywalker — Action [Exhaust, use the Force]: Play a Villainy non-unit card from your hand,
// ignoring its aspect penalties (play at printed cost). (LOF_017 Darth Revan is a combat reaction, wired
// in CombatLogic/GameLogic rather than here.)
$leaderAbilities["LOF_018"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $ready = SWUResourceCount($player, readyOnly: true);
    $targets = [];
    $hand = GetHand($player);
    for ($i = 0; $i < count($hand); $i++) {
        $c = $hand[$i]; if ($c === null || !empty($c->removed)) continue;
        $cid = $c->CardID;
        if (CardType($cid) === 'Unit') continue;                                  // non-unit only
        if (strpos(CardAspect($cid) ?? '', 'Villainy') === false) continue;       // Villainy only
        if ($ready >= intval(CardCost($cid))) $targets[] = "myHand-{$i}";          // affordable at printed cost
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_a_Villainy_non-unit_card_(ignoring_aspect_penalties)", "LOF_018#0");
};
$customDQHandlers["LOF_018#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $penalty = SWUAspectPenalty(intval($player), $o->CardID);  // discount cancels the off-aspect surcharge
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, $penalty);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    SWUAfterAction(intval($player));
};

// ── SEC_001 Chancellor Palpatine ──────────────────────────────────────────────
// Leader Action [1 resource, Exhaust]: Search the top 5 cards of your deck for a card with Plot,
// reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)
$leaderAbilities["SEC_001"] = function(int $player): void {
    global $playerID, $Plot_Cards;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; } // gate should prevent
    DoTopDeckSearch($player, 5, fn($cid) => isset($Plot_Cards[$cid]), 1);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// ── SEC_002 Jabba the Hutt ────────────────────────────────────────────────────
// Leader Action [1 resource, Exhaust]: A friendly damaged unit deals 1 damage to an enemy unit. If the
// friendly unit has 3 or more damage on it, it deals 2 damage instead. (Gate ensures both exist.)
$leaderAbilities["SEC_002"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $sources = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $sources[] = $mz;
        }
    }
    if (empty($sources)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $sources, "Choose_a_friendly_damaged_unit", "SEC_002#0");
};
$customDQHandlers["SEC_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $srcMz = $lastDecision ?? '';
    $src   = ($srcMz !== '' && str_contains($srcMz, '-')) ? GetZoneObject($srcMz) : null;
    if ($src === null || !empty($src->removed)) { SWUAfterAction(intval($player)); return; }
    $amount  = intval($src->Damage) >= 3 ? 2 : 1;   // 2 if the friendly unit has 3+ damage, else 1
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $enemies[] = $mz;
        }
    }
    if (empty($enemies)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $enemies, "Deal_{$amount}_damage_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|{$amount}");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// ── SEC_003 Lama Su ───────────────────────────────────────────────────────────
// Friendly NON-Vehicle units that are valid hosts for $upgradeCardID.
function _SWUSec003Hosts(int $player, string $upgradeCardID): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (SWUGetUpgradeValidTargets($player, $upgradeCardID) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->Controller ?? 0) !== $player) continue;   // friendly only
        if (HasTrait($o->CardID, 'Vehicle')) continue;           // non-Vehicle only
        $out[] = $mz;
    }
    return $out;
}
// Hand upgrades that have ≥1 valid non-Vehicle host and are affordable at the −1 discount.
function _SWUSec003PlayableHandUpgrades(int $player): array {
    global $playerID; $playerID = $player;
    $ready = SWUResourceCount($player, readyOnly: true);
    $out   = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Upgrade') === false) continue;
        $hosts = _SWUSec003Hosts($player, $o->CardID);
        if (empty($hosts)) continue;
        $cost = max(0, SWUComputePlayCost($player, $o, GetZoneObject($hosts[0])) - 1);
        if ($cost <= $ready) $out[] = $mz;
    }
    return $out;
}
// Leader Action [Exhaust]: Play an upgrade from your hand on a friendly non-Vehicle unit. It costs 1
// resource less. If you do, deal 1 damage to that unit. (Gate ensures a playable upgrade exists.)
$leaderAbilities["SEC_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $upgrades = _SWUSec003PlayableHandUpgrades($player);
    if (empty($upgrades)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $upgrades, "Play_an_upgrade_from_your_hand_(costs_1_less)", "SEC_003#0");
};
// Step 0: an upgrade was chosen — pick the non-Vehicle host.
$customDQHandlers["SEC_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $upMz = $lastDecision ?? '';
    $up   = ($upMz !== '' && str_contains($upMz, '-')) ? GetZoneObject($upMz) : null;
    if ($up === null || !empty($up->removed)) { SWUAfterAction(intval($player)); return; }
    $cardID = $up->CardID;
    $hosts  = _SWUSec003Hosts(intval($player), $cardID);
    if (empty($hosts)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_friendly_non-Vehicle_unit", "SEC_003#1|{$cardID}|{$upMz}");
};
// Step 1: host chosen — attach (−1 via prepaid) then deal 1 to that unit, then close the action.
$customDQHandlers["SEC_003#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $upMz   = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    $host   = ($hostMz !== '' && str_contains($hostMz, '-')) ? GetZoneObject($hostMz) : null;
    if ($cardID === '' || $host === null || !empty($host->removed)) { SWUAfterAction(intval($player)); return; }
    $hostUID = intval($host->UniqueID ?? 0);
    // prepaid=1 applies "costs 1 resource less"; suppress the helper's own After Action (we own it).
    $triggered = _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upMz, $hostMz, 1, false, false, true);
    // "If you do, deal 1 damage to that unit." Re-resolve the host by UID (the attach kept it in place).
    $hMz = SWUFindMzByUID($hostUID);
    if ($hMz !== null) SWUDealDamageToUnit($hMz, 1, intval($player));
    if ($triggered === 0) SWUAfterAction(intval($player));   // a triggered upgrade's own flush owns the close
};

// ── SEC_004 Leia Organa ───────────────────────────────────────────────────────
// Hand cards bearing at least one disclosable aspect icon (everything except Villainy).
function _SWUSec004DiscloseableHand(int $player): array {
    global $playerID; $playerID = $player;
    $five = ['Vigilance', 'Command', 'Aggression', 'Cunning', 'Heroism'];
    $out  = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (!empty(array_intersect(SWUCardAspectIcons($o->CardID), $five))) $out[] = $mz;
    }
    return $out;
}
// Shared disclose→Experience offer for both the leader Action and the deployed On Attack.
// $may = "you may disclose" (deploy) vs mandatory (leader); $closeAction = call SWUAfterAction when done
// (leader owns the close; the deployed On Attack rides combat).
function _SWUSec004Offer(int $player, bool $may, int $closeAction): void {
    global $playerID; $playerID = $player;
    $cards = _SWUSec004DiscloseableHand($player);
    if (empty($cards)) { if ($closeAction) SWUAfterAction($player); return; }
    $h = "SEC_004#0|{$closeAction}";
    if ($may) {
        SWUQueueMayChooseTarget($player, $cards, "Disclose_an_aspect?", "Choose_a_card_to_disclose", $h);
    } else {
        SWUQueueChooseTarget($player, $cards, "Disclose_an_aspect", $h);
    }
}
$leaderAbilities["SEC_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; } // gate should prevent
    _SWUSec004Offer($player, false, 1);   // mandatory disclose; leader owns the close
};
// Disclosed-card chosen → give an Experience token to a unit not sharing an aspect with it.
$customDQHandlers["SEC_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $closeAction = intval($parts[0] ?? 0);
    $mz = $lastDecision ?? '';
    if ($mz === '' || $mz === '-' || $mz === 'PASS') { if ($closeAction) SWUAfterAction(intval($player)); return; }
    $c = str_contains($mz, '-') ? GetZoneObject($mz) : null;
    if ($c === null || !empty($c->removed)) { if ($closeAction) SWUAfterAction(intval($player)); return; }
    AddGameLogEntry('DISCLOSE', 'P' . intval($player) . ' discloses ' . GameLogCardRef($c->CardID));
    $disclosedAspects = SWUCardAspectIcons($c->CardID);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $umz) {
            $o = GetZoneObject($umz);
            if ($o === null || !empty($o->removed)) continue;
            if (!empty(array_intersect(SWUCardAspectIcons($o->CardID), $disclosedAspects))) continue; // shares → out
            $targets[] = $umz;
        }
    }
    if (empty($targets)) { if ($closeAction) SWUAfterAction(intval($player)); return; }  // no valid recipient
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_an_Experience_token_to_a_unit_that_doesn't_share_an_aspect", "GIVE_EXPERIENCE|1");
    if ($closeAction) DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// ── SEC_005 Satine Kryze ──────────────────────────────────────────────────────
// Heal $amt from the unit with UID $uid, then deal the amount actually healed to $player's own base.
function _SWUSec005Apply(int $player, int $uid, int $amt): void {
    global $playerID; $playerID = $player;
    if ($amt <= 0) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    $obj = GetZoneObject($mz);
    if ($obj === null || !empty($obj->removed)) return;
    $before = intval($obj->Damage);
    OnHealUnit($player, $mz, $amt);
    $after  = intval(GetZoneObject($mz)->Damage ?? 0);
    $healed = max(0, $before - $after);
    if ($healed > 0) SWUDealDamageToBase($healed, $player);   // "deal that much to YOUR base"
}
$leaderAbilities["SEC_005"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $units[] = $mz;
        }
    }
    if (empty($units)) { SWUAfterAction($player); return; }   // gate should prevent
    SWUQueueChooseTarget($player, $units, "Heal_up_to_2_damage_from_a_unit", "SEC_005#0");
};
$customDQHandlers["SEC_005#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $uid     = intval($o->UniqueID ?? 0);
    $maxHeal = min(2, intval($o->Damage));
    if ($maxHeal <= 1) {                                  // only 1 healable → no amount choice
        _SWUSec005Apply(intval($player), $uid, $maxHeal);
        SWUAfterAction(intval($player));
        return;
    }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Heal1&Heal2", 1,
        tooltip: "Heal_up_to_2_(you_then_deal_that_much_to_your_base)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_005#1|{$uid}", 1);
};
$customDQHandlers["SEC_005#1"] = function($player, $parts, $lastDecision) {
    $uid = intval($parts[0] ?? 0);
    $amt = ($lastDecision === 'Heal2') ? 2 : 1;
    _SWUSec005Apply(intval($player), $uid, $amt);
    SWUAfterAction(intval($player));
};

// ── SEC_006 Colonel Yularen ───────────────────────────────────────────────────
// Action [Exhaust]: Attack with a unit. Then, you may attack with another unit that costs less than it.
$leaderAbilities["SEC_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $units[] = $mz;
        }
    }
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $units, "Attack_with_a_unit", "SEC_006#0");
};
$customDQHandlers["SEC_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $uid  = intval($o->UniqueID ?? 0);
    $cost = intval(CardCost($o->CardID));
    // "you may attack with another unit that costs less than it" → costlt:{cost}, may-decline.
    SetSWUVar('SWU_CHAINED_ATTACK', "0,1,0,{$uid},costlt:{$cost}");
    BeginSWUAttack(intval($player), $mz);   // combat owns SWUAfterAction; the chain rides the resume
};

// ── SEC_007 Dryden Vos ────────────────────────────────────────────────────────
// Action [Exhaust, discard a card that costs 6 or more]: Play a unit that costs 5 or less from your hand
// (paying its cost). It gains Ambush for this phase.
$leaderAbilities["SEC_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $discardable = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) >= 6) $discardable[] = $mz;
    }
    if (empty($discardable)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $discardable, "Discard_a_card_costing_6_or_more", "SEC_007#0");
};
// Step 0: 6+ card chosen → discard it (the additional cost), then choose a ≤5 unit to play.
$customDQHandlers["SEC_007#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $mz);                       // pay the additional cost
    DecisionQueueController::CleanupRemovedCards();
    $ready = SWUResourceCount(intval($player), readyOnly: true);
    $units = [];
    foreach (ZoneSearch('myHand') as $hmz) {
        $u = GetZoneObject($hmz);
        if ($u === null || !empty($u->removed)) continue;
        if (stripos(CardType($u->CardID) ?? '', 'Unit') === false) continue;
        if (intval(CardCost($u->CardID)) > 5) continue;
        if (SWUComputePlayCost(intval($player), $u) > $ready) continue;
        $units[] = $hmz;
    }
    if (empty($units)) { SWUAfterAction(intval($player)); return; } // discarded but nothing affordable to play
    SWUQueueChooseTarget(intval($player), $units, "Play_a_unit_costing_5_or_less_(it_gains_Ambush)", "SEC_007#1");
};
// Step 1: ≤5 unit chosen → play it (paying its cost) with Ambush this phase.
$customDQHandlers["SEC_007#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_007';                         // the played unit gains Ambush this phase
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, false);                // pays the unit's cost; inner swap neutralised
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    SWUAfterAction(intval($player));
};

// ── SEC_008 Bail Organa ───────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: If a friendly unit was defeated this phase, return a friendly resource to
// its owner's hand. If you do, put the top card of your deck into play as a resource.
$leaderAbilities["SEC_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    if (GlobalEffectCount($player, 'SWU_FRIENDLY_DEFEATED') <= 0) { SWUAfterAction($player); return; } // condition false
    $res = &GetResources($player);
    $targets = [];
    for ($i = 0, $idx = 0; $i < count($res); $i++) {
        if (isset($res[$i]->removed) && $res[$i]->removed) continue;
        $targets[] = "myResources-{$idx}"; $idx++;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Return_a_friendly_resource_to_its_owner's_hand", "SEC_008#0");
};
$customDQHandlers["SEC_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || !str_contains($mz, '-')) { SWUAfterAction(intval($player)); return; }
    if (!SWUReturnResourceToHand(intval($player), $mz)) { SWUAfterAction(intval($player)); return; }
    DecisionQueueController::CleanupRemovedCards();
    // "If you do, put the top card of your deck into play as a resource." (enters exhausted)
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < count($deck); $i++) {
        if (isset($deck[$i]->removed) && $deck[$i]->removed) continue;
        $top = $deck[$i]->CardID; $deck[$i]->Remove();
        AddResources(intval($player), $top, 0, intval($player), intval($player)); // Status 0 = exhausted
        AddGameLogEntry('RESOURCE', 'P' . intval($player) . ' put a card into play as a resource');
        break;
    }
    DecisionQueueController::CleanupRemovedCards();
    SWUAfterAction(intval($player));
};

// ── SEC_010 Dedra Meero ───────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may deal 2 damage to it. If they
// don't, draw a card. (Cross-player YESNO for the opponent; the caster draws on a decline.)
$leaderAbilities["SEC_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $enemies[] = $mz;
        }
    }
    if (empty($enemies)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $enemies, "Choose_an_enemy_unit", "SEC_010#0");
};
// Step 0 (caster frame): the chosen enemy unit → hand the opponent a YESNO from a CUSTOM (safe for the
// cross-player $playerID handoff).
$customDQHandlers["SEC_010#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $uid = intval($o->UniqueID ?? 0);
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;   // the opponent owns the next decision
    DecisionQueueController::AddDecision($opp, 'YESNO', '-', 1, tooltip: "Deal_2_damage_to_your_own_unit?");
    DecisionQueueController::AddDecision($opp, 'CUSTOM', "SEC_010#1|" . intval($player) . "|{$uid}", 1);
};
// Step 1 (opponent frame): YES → opponent deals 2 to the unit; NO → the caster draws. Caster closes.
$customDQHandlers["SEC_010#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    $uid    = intval($parts[1] ?? 0);
    if (($lastDecision ?? '') === 'YES') {
        $playerID = intval($player);                 // opponent's frame
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    } else {
        DoDrawCard($caster, 1);                       // "If they don't, draw a card." (the caster draws)
    }
    SWUAfterAction($caster);
};

// ── SEC_011 Governor Pryce ────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: Ready a token unit. (Offers friendly exhausted token units.)
$leaderAbilities["SEC_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $tokens = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && EffectiveCardType($o) === 'Token Unit'
                    && intval($o->Status ?? 0) !== 1) $tokens[] = $mz;
        }
    }
    if (empty($tokens)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $tokens, "Ready_a_token_unit", "READY_UNIT");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// ── SEC_014 Sly Moore ─────────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: If there are 4 or more exhausted units in play, create a Spy token.
$leaderAbilities["SEC_014"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $exh = 0;
    foreach ([1, 2] as $p) {
        foreach (GetUnitsInPlay($p) as $u) {
            if ($u !== null && empty($u->removed) && intval($u->Status ?? 0) !== 1) $exh++;
        }
    }
    if ($exh >= 4) SWUCreateUnitToken($player, 'SEC_T01');   // Spy
    SWUAfterAction($player);
};

// ── SEC_015 C-3PO ─────────────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: If you control an exhausted unit, exhaust a unit.
$leaderAbilities["SEC_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $hasExh = false;
    foreach (GetUnitsInPlay($player) as $u) {
        if (empty($u->removed) && intval($u->Status ?? 0) !== 1) { $hasExh = true; break; }
    }
    if (!$hasExh) { SWUAfterAction($player); return; }   // condition false → no-op (still paid+exhausted)
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz; // ready
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Exhaust_a_unit", "EXHAUST_UNIT");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// ── SEC_018 DJ ────────────────────────────────────────────────────────────────
// Action [Exhaust]: Choose a friendly unit. If you do, play a unit from your hand. It costs 1 resource
// less. The chosen unit captures it. (When Played abilities resolve after the unit is captured — here
// the captured unit's When Played fires on the normal play path, before capture; an edge for non-vanilla.)
$leaderAbilities["SEC_018"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $captors = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $captors[] = $mz;
        }
    }
    if (empty($captors)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $captors, "Choose_a_friendly_unit_to_capture_with", "SEC_018#0");
};
// Step 0: captor chosen → choose a hand unit to play (affordable at the −1 discount).
$customDQHandlers["SEC_018#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $captorMz = $lastDecision ?? '';
    $captor   = ($captorMz !== '' && str_contains($captorMz, '-')) ? GetZoneObject($captorMz) : null;
    if ($captor === null || !empty($captor->removed)) { SWUAfterAction(intval($player)); return; }
    $captorUID = intval($captor->UniqueID ?? 0);
    $ready = SWUResourceCount(intval($player), readyOnly: true);
    $handUnits = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        if (max(0, SWUComputePlayCost(intval($player), $o) - 1) > $ready) continue;
        $handUnits[] = $mz;
    }
    if (empty($handUnits)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $handUnits, "Play_a_unit_from_hand_(costs_1_less)", "SEC_018#1|{$captorUID}");
};
// Step 1: play the chosen unit (−1) with a findable marker, then the captor captures it.
$customDQHandlers["SEC_018#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID  = intval($player);
    $captorUID = intval($parts[0] ?? 0);
    $handMz    = $lastDecision ?? '';
    if ($handMz === '' || !str_contains($handMz, '-')) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_018';                        // findable marker on the played unit
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $handMz, false, 1);        // −1 discount; inner after-action neutralised
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                    && in_array('SEC_018', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }
    $captorMz = SWUFindMzByUID($captorUID);
    if ($newMz !== null && $captorMz !== null) DoCaptureUnit(intval($player), $captorMz, $newMz);
    SWUAfterAction(intval($player));
};

// ══════════════════════════════════════════════════════════════════════════════
// LAW Phase 8 — two-sided leaders (front Action via $leaderAbilities; deployed side
// via the unit-ability registries keyed on the leader CardID).
// ══════════════════════════════════════════════════════════════════════════════

// ── LAW_001 Saw Gerrera ───────────────────────────────────────────────────────
// Front Action [Exhaust]: attack with a unit (+2/+0 + Overwhelm this attack; defeat it after).
// Deployed When Attack Ends: if Saw survived, may attack with ANOTHER unit (same grants + self-defeat).
function _SWULaw001AttackWith(int $player, string $attackerMz): void {
    global $playerID; $playerID = $player;
    SWUAddAttackPowerBonus($attackerMz, 2);
    AddTurnEffect($attackerMz, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK));
    AddTurnEffect($attackerMz, SWUMakeTurnEffect('LAW_062', [], SWU_DUR_ATTACK)); // unconditional self-defeat after attack
    BeginSWUAttack($player, $attackerMz);   // owns SWUAfterAction once it attacks
}
$leaderAbilities["LAW_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Attack_with_a_unit_(+2/+0,_Overwhelm,_then_defeat_it)", "LAW_001#0");
};
$customDQHandlers["LAW_001#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    _SWULaw001AttackWith(intval($player), $lastDecision);
};
$onAttackEndAbilities["LAW_001:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || !empty($self->removed)) return;   // Saw didn't survive
    $selfUid = intval($self->UniqueID ?? 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUid && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Attack_with_another_unit_(+2/+0,_Overwhelm,_then_defeat_it)?", "Choose_a_unit", "LAW_001_ATKEND");
};
$customDQHandlers["LAW_001_ATKEND"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    _SWULaw001AttackWith(intval($player), $lastDecision);
};

// ── LAW_002 Tobias Beckett ────────────────────────────────────────────────────
// Front Action [Exhaust]: choose a friendly unit; an opponent takes control of it; if they do, create
// a Credit token. Deployed When Deployed: defeat any number of units you OWN but don't control; for
// each, create a Credit token and draw a card.
$leaderAbilities["LAW_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_friendly_unit_to_an_opponent_(then_create_a_Credit)", "LAW_002#0");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$customDQHandlers["LAW_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    SWUTakeControlOfUnit(OtherPlayer(intval($player)), $lastDecision);   // opponent takes control
    SWUCreateCreditToken(intval($player), 1);
};
$whenPlayedAbilities["LAW_002:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // units you OWN but don't control = your-owned units sitting in the opponent's arenas
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Owner ?? 0) === intval($player)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    $max = count($targets);
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $targets), 1, tooltip: "Defeat_any_number_of_units_you_own_but_don't_control");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_002_DEPLOY", 1);
};
$customDQHandlers["LAW_002_DEPLOY"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $uids = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        if ($uid <= 0) continue;
        $mz = SWUFindMzByUID($uid);
        if ($mz === null) continue;
        SWUDefeatUnit(intval($player), $mz);
        SWUCreateCreditToken(intval($player), 1);
        DoDrawCard(intval($player), 1);
    }
};

// ── LAW_003 Agent Kallus ──────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust] AND deployed Action [1 resource]: play a card from hand ignoring
// its aspect penalties. Deployed also: "When you play a Heroism card: heal 2 from your base" (in
// SWUCollectOwnPlayReactions). The deployed unit Action is registered in CardDQHandlers (after the
// $unitAbilities reset).
function _SWULaw003OfferPlay(int $player): void {
    global $playerID; $playerID = $player;
    $ready = SWUResourceCount($player, readyOnly: true);
    $hand  = GetHand($player);
    $targets = [];
    for ($i = 0; $i < count($hand); $i++) {
        $c = $hand[$i];
        if ($c === null || !empty($c->removed)) continue;
        $cid = $c->CardID;
        if (_SWUCantPlayFromHand($cid)) continue;
        $eff = max(0, SWUComputePlayCost($player, $c) - SWUAspectPenalty($player, $cid));
        if ($ready >= $eff) $targets[] = "myHand-{$i}";
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_a_card_ignoring_its_aspect_penalties", "LAW_003_PLAY");
};
$customDQHandlers["LAW_003_PLAY"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $discount = SWUAspectPenalty(intval($player), $o->CardID);   // waive the FULL aspect penalty
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, $discount);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    SWUAfterAction(intval($player));
};
$leaderAbilities["LAW_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    _SWULaw003OfferPlay($player);
};
$leaderActionResourceCosts["LAW_003"] = 1;

// ── LAW_015 Jabba the Hutt ─────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust, return a friendly Underworld unit to its owner's hand]: Create a
// Credit token. Deployed Action: Play an Underworld unit from your hand; if you defeated a Credit while
// paying its cost, it gains Ambush this phase (deployed side + the conditional-Ambush plumbing live in
// CardDQHandlers.php / GameLogic.php). The leader is exhausted by SWULeaderAction; this closure pays the
// 1 resource then the return-a-unit additional cost (mandatory) before the effect.
function _SWULaw015FriendlyUnderworldUnits(int $player): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && _SWUUnitHasTrait($o, 'Underworld')) $out[] = $mz;
        }
    }
    return $out;
}
$leaderAbilities["LAW_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; } // affordability-gated; defensive
    $targets = _SWULaw015FriendlyUnderworldUnits($player);
    if (empty($targets)) { SWUAfterAction($player); return; } // defensive (affordability requires one)
    SWUQueueChooseTarget($player, $targets, "Return_a_friendly_Underworld_unit_to_its_owner's_hand", "LAW_015_FRONT");
};
$leaderActionResourceCosts["LAW_015"] = 1;
$customDQHandlers["LAW_015_FRONT"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) SWUBounceUnit(intval($player), $lastDecision); // return-to-hand cost
    }
    SWUCreateCreditToken(intval($player), 1);
    SWUAfterAction(intval($player));
};

// ── LAW_004 Aurra Sing ────────────────────────────────────────────────────────
// Front Action [Exhaust]: defeat a non-leader unit with 1 or less remaining HP.
// Deployed When Deployed: you MAY defeat a non-leader unit with 5 or less remaining HP.
function _SWULaw004Targets(int $player, int $maxRemainingHP): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= $maxRemainingHP) $out[] = $mz;
        }
    }
    return $out;
}
$leaderAbilities["LAW_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = _SWULaw004Targets($player, 1);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Defeat_a_non-leader_unit_with_1_or_less_remaining_HP", "DEFEAT_UNIT");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$whenPlayedAbilities["LAW_004:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWULaw004Targets(intval($player), 5);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_with_5_or_less_remaining_HP?", "Choose_a_unit", "DEFEAT_UNIT");
};

// ── LAW_005 Jyn Erso ──────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust] / deployed On Attack: if a friendly Rebel unit was defeated this
// phase, search the top 3 of your deck for a card and draw it.
function _SWULaw005Search(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_REBEL_DEFEATED') <= 0) { SWUAfterAction($player); return; }
    if (count(GetDeck($player)) === 0) { SWUAfterAction($player); return; }
    DoTopDeckSearch($player, 3, fn($c) => true, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
}
$leaderAbilities["LAW_005"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    _SWULaw005Search($player);
};
$leaderActionResourceCosts["LAW_005"] = 1;
$onAttackAbilities["LAW_005:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_REBEL_DEFEATED') <= 0) return;
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 3, fn($c) => true, 1);
};

// ── LAW_006 Vel Sartha ────────────────────────────────────────────────────────
// Front Action [Exhaust]: give an Experience token to a unit; an opponent creates a Credit token.
// Deployed On Attack: MAY give an Experience token; if you do, an opponent creates a Credit token.
$customDQHandlers["LAW_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    DoGiveExperienceToken(intval($player), $lastDecision);
    SWUCreateCreditToken(OtherPlayer(intval($player)), 1);   // "an opponent creates a Credit token"
};
$leaderAbilities["LAW_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_(an_opponent_creates_a_Credit)", "LAW_006#0");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$onAttackAbilities["LAW_006:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_unit_(opponent_creates_a_Credit)?", "Choose_a_unit", "LAW_006#0");
};

// ── LAW_007 Boba Fett ─────────────────────────────────────────────────────────
// Combat observer in CombatLogic (Bounty-Hunter attack ends + defender defeated). The leader-form
// may-exhaust→Credit resolves here:
$customDQHandlers["LAW_007#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    _SWUExhaustUndeployedLeader(intval($player), 'LAW_007');
    SWUCreateCreditToken(intval($player), 1);
};

// ── LAW_008 Director Krennic ──────────────────────────────────────────────────
// Front Action [Exhaust, defeat a friendly unit]: create a Credit token.
$customDQHandlers["LAW_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUDefeatUnit(intval($player), $lastDecision);   // pay the [defeat a friendly unit] cost
    SWUCreateCreditToken(intval($player), 1);
};
$leaderAbilities["LAW_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $friendly[] = $mz; }
    if (empty($friendly)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $friendly, "Defeat_a_friendly_unit_(cost)_to_create_a_Credit", "LAW_008#0");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
// Deployed When Deployed: another friendly unit deals damage equal to its power to an enemy unit.
$whenPlayedAbilities["LAW_008:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = $self ? intval($self->UniqueID ?? 0) : 0;
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $fo = GetZoneObject($mz);
            if ($fo !== null && empty($fo->removed) && intval($fo->UniqueID ?? 0) !== $selfUid) $friendly[] = $mz;
        }
    $enemyExists = !empty(ZoneSearch('theirGroundArena', AnyUnitFilter)) || !empty(ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($friendly) || !$enemyExists) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Choose_another_friendly_unit_to_deal_its_power", "LAW_008_DEPLOY");
};
$customDQHandlers["LAW_008_DEPLOY"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $enemies = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Choose_an_enemy_unit", "SOR_127#1|" . $lastDecision, 0);  // reuse SOR_127#1 deal-power
};

// ── LAW_009 Hera Syndulla — passive cost-waive only (in SWUComputePlayCost); no front Action. ────────

// ── LAW_010 Leia Organa ───────────────────────────────────────────────────────
// Front Action [2 resources, Exhaust]: give a unit +1/+1 for this phase for each different aspect IT
// has. Deployed When Deployed: give a chosen unit an Experience token for each different aspect AMONG
// units you control.
$leaderActionResourceCosts["LAW_010"] = 2;
$leaderAbilities["LAW_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 2)) { SWUAfterAction($player); return; }
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $units[] = $mz; }
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $units, "Give_a_unit_+1/+1_per_different_aspect_it_has", "LAW_010#0");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$customDQHandlers["LAW_010#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $asp = [];
    foreach (explode(',', (string)(CardAspect($o->CardID ?? '') ?? '')) as $a) { $a = trim($a); if ($a !== '') $asp[$a] = true; }
    $n = count($asp);
    if ($n > 0) SWUApplyPhaseBuff($lastDecision, $n, $n, 'LAW_010');
};
$whenPlayedAbilities["LAW_010:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $units[] = $mz; }
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Give_Experience_tokens_(=_distinct_aspects_you_control)_to_a_unit", "LAW_010_DEPLOY");
};
$customDQHandlers["LAW_010_DEPLOY"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $asp = [];
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        foreach (explode(',', (string)(CardAspect($u->CardID ?? '') ?? '')) as $a) { $a = trim($a); if ($a !== '') $asp[$a] = true; }
    }
    $n = count($asp);
    for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $lastDecision);
};

// ── LAW_011 Darth Vader ───────────────────────────────────────────────────────
// Front Action [Exhaust, discard a card from your hand]: deal 1 damage to a unit or base.
// Deployed On Attack: discard any number of cards from hand; deal that many to a unit or base.
$leaderAbilities["LAW_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $hand, "Discard_a_card_from_your_hand_(cost)", "LAW_011#0");
};
$customDQHandlers["LAW_011#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    $targets = _SWUAllUnitsAndBases(intval($player));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_unit_or_base", "DEAL_TARGET|1");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$onAttackAbilities["LAW_011:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) return;
    $max = count($hand);
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $hand), 1, tooltip: "Discard_any_number_of_cards_from_your_hand");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_011_ATK", 1);
};
$customDQHandlers["LAW_011_ATK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $mzs = array_filter(explode('&', $lastDecision), fn($m) => $m !== '' && $m !== '-' && $m !== 'PASS');
        // discard highest hand index first so earlier indices don't shift out from under later picks
        usort($mzs, fn($a, $b) => intval(substr(strrchr($b, '-'), 1)) <=> intval(substr(strrchr($a, '-'), 1)));
        foreach ($mzs as $mz) { DoDiscardCard(intval($player), $mz); $n++; }
    }
    DecisionQueueController::CleanupRemovedCards();
    if ($n <= 0) return;
    $targets = _SWUAllUnitsAndBases(intval($player));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_{$n}_damage_to_a_unit_or_base", "DEAL_TARGET|{$n}");
};

// ── LAW_012 Sebulba ───────────────────────────────────────────────────────────
// Front Action [Exhaust, discard a card from your deck]: a friendly unit gains Raid 1 for this phase.
// Deployed: Raid 1 (auto) + On Attack: discard a card from your deck.
$leaderAbilities["LAW_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $friendly[] = $mz; }
    if (empty($friendly)) { SWUAfterAction($player); return; }
    SWUMillTopCard($player);   // pay the [discard a card from your deck] cost
    SWUQueueChooseTarget($player, $friendly, "A_friendly_unit_gains_Raid_1_for_this_phase", "LAW_012#0");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$customDQHandlers["LAW_012#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'LAW_012');   // Raid 1 this phase
};
$onAttackAbilities["LAW_012:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUMillTopCard(intval($player));
};

// ── LAW_013 Chewbacca ─────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust, defeat a friendly resource]: deal 2 to a unit and create a Credit.
// Deployed On Attack: you MAY defeat a friendly resource → deal 2 to a unit and create a Credit.
function _SWULaw013Payoff(int $player): void {   // after the resource is defeated: credit + deal 2 to a unit
    global $playerID; $playerID = $player;
    SWUCreateCreditToken($player, 1);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $units[] = $mz; }
    if (empty($units)) return;
    SWUQueueChooseTarget($player, $units, "Deal_2_damage_to_a_unit", "DEAL_UNIT_DAMAGE|2");
}
$leaderActionResourceCosts["LAW_013"] = 1;
$leaderAbilities["LAW_013"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $res = &GetResources($player);
    $resTargets = [];
    for ($i = 0, $idx = 0; $i < count($res); $i++) { if (isset($res[$i]->removed) && $res[$i]->removed) continue; $resTargets[] = "myResources-{$idx}"; $idx++; }
    if (empty($resTargets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $resTargets, "Defeat_a_friendly_resource_(cost)", "LAW_013#0");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$customDQHandlers["LAW_013#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUDefeatResource(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    _SWULaw013Payoff(intval($player));
};
$onAttackAbilities["LAW_013:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $res = &GetResources(intval($player));
    $resTargets = [];
    for ($i = 0, $idx = 0; $i < count($res); $i++) { if (isset($res[$i]->removed) && $res[$i]->removed) continue; $resTargets[] = "myResources-{$idx}"; $idx++; }
    if (empty($resTargets)) return;
    SWUQueueMayChooseTarget(intval($player), $resTargets, "Defeat_a_friendly_resource_to_deal_2_and_create_a_Credit?", "Choose_a_resource", "LAW_013_ATK");
};
$customDQHandlers["LAW_013_ATK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    SWUDefeatResource(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    _SWULaw013Payoff(intval($player));
};

// ── LAW_016 The Client ────────────────────────────────────────────────────────
// Front Action [Exhaust]: if you created a token this phase, exhaust an enemy unit.
// Deployed: Shielded (auto) + On Attack: if you created a token this phase, exhaust an enemy unit.
function _SWULaw016Enemies(int $player): array {
    global $playerID; $playerID = $player;
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $enemies[] = $mz; }
    return $enemies;
}
$leaderAbilities["LAW_016"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_CREATED_TOKEN') <= 0) { SWUAfterAction($player); return; }
    $enemies = _SWULaw016Enemies($player);
    if (empty($enemies)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$onAttackAbilities["LAW_016:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_CREATED_TOKEN') <= 0) return;
    $enemies = _SWULaw016Enemies(intval($player));
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
};

// ── LAW_017 Han Solo ──────────────────────────────────────────────────────────
// Front Action [Exhaust, defeat a friendly token]: deal 1 to a unit.
// Deployed: Saboteur (auto) + On Attack: defeat any number of friendly tokens; deal that many to a unit.
// "Friendly token" = Token Units (arena) + Credit tokens.
function _SWULaw017Tokens(int $player): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, ["Token Unit"]) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $out[] = $mz; }
    foreach (SWUUsableCreditTokenMzIDs($player) as $mz) $out[] = $mz;
    return $out;
}
function _SWULaw017DealNToUnit(int $player, int $n): void {
    if ($n <= 0) return;
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $units[] = $mz; }
    if (empty($units)) return;
    SWUQueueChooseTarget($player, $units, "Deal_{$n}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$n}");
}
$leaderAbilities["LAW_017"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $tokens = _SWULaw017Tokens($player);
    if (empty($tokens)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $tokens, "Defeat_a_friendly_token_(cost)", "LAW_017#0");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};
$customDQHandlers["LAW_017#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    if (($o->CardID ?? '') === 'LAW_T01') SWUDefeatCreditToken($lastDecision); else SWUDefeatUnit(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    _SWULaw017DealNToUnit(intval($player), 1);
};
$onAttackAbilities["LAW_017:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tokens = _SWULaw017Tokens(intval($player));
    if (empty($tokens)) return;
    $max = count($tokens);
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tokens), 1, tooltip: "Defeat_any_number_of_friendly_tokens");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_017_ATK", 1);
};
$customDQHandlers["LAW_017_ATK"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $mzs = array_filter(explode('&', $lastDecision), fn($m) => $m !== '' && $m !== '-' && $m !== 'PASS');
    // Token units: defeat by UID (stable). Credits: count then re-resolve each (index-shift safe).
    $creditCount = 0; $unitUids = [];
    foreach ($mzs as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (($o->CardID ?? '') === 'LAW_T01') $creditCount++; else $unitUids[] = intval($o->UniqueID ?? 0);
    }
    $n = $creditCount + count($unitUids);
    foreach ($unitUids as $uid) { if ($uid <= 0) continue; $m = SWUFindMzByUID($uid); if ($m !== null) SWUDefeatUnit(intval($player), $m); }
    for ($i = 0; $i < $creditCount; $i++) { $cr = SWUUsableCreditTokenMzIDs(intval($player)); if (!empty($cr)) SWUDefeatCreditToken($cr[0]); }
    DecisionQueueController::CleanupRemovedCards();
    _SWULaw017DealNToUnit(intval($player), $n);
};

// ── LAW_018 Lando Calrissian ──────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: choose an aspect, then discard a card from a deck. If it has the
// chosen aspect, create a Credit token. Deployed When Deployed: you MAY defeat a friendly Credit token;
// if you do, create 3 Credit tokens.
function _SWULaw018Mill(int $player, int $deckOwner, string $aspect): void {
    global $playerID; $playerID = $player;
    $cid = SWUMillTopCard($deckOwner);
    if ($cid !== null && strpos((string)(CardAspect($cid) ?? ''), $aspect) !== false) SWUCreateCreditToken($player, 1);
    SWUAfterAction($player);
}
$leaderActionResourceCosts["LAW_018"] = 1;
$leaderAbilities["LAW_018"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Vigilance&Command&Aggression&Cunning&Heroism&Villainy", 1, "Choose_an_aspect");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_018#0", 1);
};
$customDQHandlers["LAW_018#0"] = function($player, $parts, $lastDecision) {   // $lastDecision = aspect
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') { SWUAfterAction(intval($player)); return; }
    $aspect = $lastDecision;
    $opp = OtherPlayer(intval($player));
    $mine = _SWUTopDeckFrontIdx(intval($player)) !== -1;
    $theirs = _SWUTopDeckFrontIdx($opp) !== -1;
    if (!$mine && !$theirs) { SWUAfterAction(intval($player)); return; }
    if ($mine && !$theirs) { _SWULaw018Mill(intval($player), intval($player), $aspect); return; }
    if ($theirs && !$mine) { _SWULaw018Mill(intval($player), $opp, $aspect); return; }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@-&Your_deck&Opponent's_deck", 1, "Discard_from_which_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_018#1|" . $aspect, 1);
};
$customDQHandlers["LAW_018#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $aspect = $parts[0] ?? '';
    $owner = ($lastDecision === "Opponent's_deck") ? OtherPlayer(intval($player)) : intval($player);
    _SWULaw018Mill(intval($player), $owner, $aspect);
};
$whenPlayedAbilities["LAW_018:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (empty(SWUUsableCreditTokenMzIDs(intval($player)))) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Defeat_a_friendly_Credit_token_to_create_3_Credit_tokens?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_018_DEPLOY", 1);
};
$customDQHandlers["LAW_018_DEPLOY"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $credits = SWUUsableCreditTokenMzIDs(intval($player));
    if (empty($credits)) return;
    if (SWUDefeatCreditToken($credits[0])) SWUCreateCreditToken(intval($player), 3);
};

?>
