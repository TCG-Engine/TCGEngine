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
    SWUQueueAfterAction($player);
};

// IBH_053 Darth Vader — Leader Action [1 resource, Exhaust]: deal 1 damage to a base.
$leaderAbilities["IBH_053"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, ['myBase-0', 'theirBase-0'], "Deal_1_damage_to_a_base", "DEAL_BASE_DAMAGE|1");
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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

// TWI_002 Nute Gunray (front) — "Action [Exhaust]: If 2 or more friendly units were defeated this phase,
// create a Battle Droid token." (Affordability gates the ≥2 condition.)
$leaderAbilities["TWI_002"] = function(int $player): void {
    SWUCreateUnitToken($player, 'TWI_T01');
    SWUAfterAction($player);
};

// TWI_003 Obi-Wan Kenobi (front) — "Action [Exhaust]: Heal 1 damage from a unit."
$leaderAbilities["TWI_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Heal_1_damage_from_a_unit", "HEAL_TARGET|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// TWI_004 Yoda (front) — "Action [Exhaust]: If a unit left play this phase, draw a card, then put a card
// from your hand on the top or bottom of your deck." (Affordability gates the left-play condition.)
$leaderAbilities["TWI_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    DoDrawCard($player, 1);
    DecisionQueueController::CleanupRemovedCards();
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $hand, "Put_a_card_on_the_top_or_bottom_of_your_deck", "TWI_004#0");
};

// TWI_013 Mace Windu (front) — "Action [1 resource, Exhaust]: Deal 1 damage to a damaged enemy unit.
// Then, if it has 5 or more damage on it, deal 1 damage to it." (Resource + damaged-enemy gated.)
$leaderActionResourceCosts["TWI_013"] = 1;
$leaderAbilities["TWI_013"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_to_a_damaged_enemy_unit_(then_1_more_if_5+_damage)", "TWI_013#0");
};

// TWI_014 Asajj Ventress (front) — "Action [Exhaust]: Attack with a unit. If you played an event this
// phase, it gets +1/+0 for this attack."
$leaderAbilities["TWI_014"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit", "TWI_014#0");
};

// TWI_015 General Grievous (front) — "Action [Exhaust]: Give a Droid unit Sentinel for this phase."
$leaderAbilities["TWI_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $droids = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Droid')) $droids[] = $mz;
        }
    }
    if (empty($droids)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $droids, "Give_a_Droid_unit_Sentinel_this_phase", "TWI_015#0");
};

// TWI_009 Maul (front) — "Action [Exhaust]: Attack with a unit. It gains Overwhelm for this attack."
$leaderAbilities["TWI_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit_(gains_Overwhelm)", "TWI_009#0");
};

// TWI_010 Pre Vizsla (front) — "Action [1 resource, Exhaust]: Deal damage to a unit equal to the number
// of cards you've drawn this phase."
$leaderActionResourceCosts["TWI_010"] = 1;
$leaderAbilities["TWI_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $n = GlobalEffectCount($player, 'SWU_DREW_PHASE');
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if ($n <= 0 || empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_{$n}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$n}");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// TWI_011 Ahsoka Tano (front) — "Coordinate - Action [Exhaust]: Attack with a unit. It gets +1/+0 for
// this attack." (Coordinate gated in affordability.)
$leaderAbilities["TWI_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit_(+1/+0)", "TWI_011#0");
};

// TWI_012 Anakin Skywalker (front) — "Action [Exhaust, deal 2 damage to your base]: Attack with a unit.
// If it's attacking a unit, it gets +2/+0 for this attack."
$leaderAbilities["TWI_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    SWUDealDamageToBase(2, $player, $player); // additional cost
    $ready = _SWUReadyFriendlyUnits($player);
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Attack_with_a_unit_(+2/+0_if_attacking_a_unit)", "TWI_012#0");
};

// TWI_006 Wat Tambor (front) — "Action [Exhaust]: If a friendly unit was defeated this phase, give a unit
// +2/+2 for this phase." (Affordability gates the defeat condition.)
$leaderAbilities["TWI_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_unit_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|TWI_006");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// TWI_007 Captain Rex (front) — "Action [2 resources, Exhaust]: If a friendly unit attacked this phase,
// create a Clone Trooper token." (Resource cost + attacked condition gated in affordability.)
$leaderActionResourceCosts["TWI_007"] = 2;
$leaderAbilities["TWI_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 2)) { SWUAfterAction($player); return; }
    SWUCreateUnitToken($player, 'TWI_T02');
    SWUAfterAction($player);
};

// TWI_008 Padmé Amidala (front) — "Coordinate - Action [1 resource, Exhaust]: Search the top 3 cards of
// your deck for a Republic card, reveal it, and draw it." (Coordinate + resource gated in affordability.)
$leaderActionResourceCosts["TWI_008"] = 1;
$leaderAbilities["TWI_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    if (count(GetDeck($player)) > 0) DoTopDeckSearch($player, 3, fn($c) => HasTrait($c, 'Republic'), 1);
    SWUQueueAfterAction($player);
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
    // Twin Suns: mutate the JTL_013 leader specifically (a seat may hold two leaders); leader CardIDs
    // are unique per seat. Fall back to first live for a single-leader game.
    $ldr = SWUFindLeaderByCardID($player, 'JTL_013');
    if ($ldr === null) $ldr = SWUGetLeaderByIndex($player, 0);
    if ($ldr !== null) {
        $ldr->Deployed        = true;
        $ldr->DeployedUniqueID = 0; // attached as subcard, no standalone arena UID
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

// TWI_017 Chancellor Palpatine "Flipatine" — a double-leader-face FLIP card with no unit side. Its
// Deployed flag is repurposed as the FACE bit: false = Heroism face (Cunning+Heroism), true = flipped
// Villainy face (Cunning+Villainy); flipping never creates an arena unit. Both faces are Action [Exhaust]
// abilities; SWULeaderAction already exhausted the leader before this closure runs, and the flip leaves it
// exhausted (ruling). Ruling: the Action is always usable when ready — if the face's condition isn't met,
// NONE of the listed effects resolve (not even the flip); the leader is simply spent.
$leaderAbilities["TWI_017"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $leaderArr = &GetLeader($player);
    $lead = null;
    for ($i = 0; $i < count($leaderArr); $i++) {
        if (empty($leaderArr[$i]->removed) && ($leaderArr[$i]->CardID ?? '') === 'TWI_017') { $lead = &$leaderArr[$i]; break; }
    }
    if ($lead === null) { SWUAfterAction($player); return; }
    if (empty($lead->Deployed)) {
        // HEROISM face: if a friendly Heroism unit was defeated this phase → draw 1, heal 2 from your base, flip.
        if (GlobalEffectCount($player, 'SWU_FRIENDLY_HEROISM_DEFEATED') > 0) {
            DoDrawCard($player, 1);
            OnHealBase($player, $player, 2);
            $lead->Deployed = true;  // flip to the Villainy face
        }
    } else {
        // VILLAINY face: if you played a Villainy card this phase → create a Clone Trooper, deal 2 to each enemy base, flip.
        if (GlobalEffectCount($player, 'SWU_PLAYED_VILLAINY') > 0) {
            SWUCreateUnitToken($player, 'TWI_T02');                 // Clone Trooper token
            SWUDealDamageToBase(2, OtherPlayer($player));           // 2-player: the single enemy base
            $lead->Deployed = false; // flip back to the Heroism face
        }
    }
    SWUAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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

// LOF_001 Kylo Ren (deployed) — When Deployed: Play any number of upgrades from your discard pile
// on THIS unit (one at a time, paying their costs). Self-re-queuing loop: offer playable discard
// upgrades attachable to Kylo → on a pick, pay full cost + attach onto Kylo, then re-offer;
// decline or none-left stops. The deploy action owns the After Action (entry trigger).
$whenPlayedAbilities["LOF_001:0"] = function($player, $mzID) {
    $host = GetZoneObject($mzID);
    $uid  = $host ? intval($host->UniqueID ?? -1) : -1;
    if ($uid < 0) return;
    _SWULof001Offer(intval($player), $uid);
};
function _SWULof001Offer(int $player, int $hostUID): void {
    global $playerID; $playerID = $player;
    $hostMz = SWUFindMzByUID($hostUID);
    if ($hostMz === null) return;
    $hostObj = GetZoneObject($hostMz);
    if ($hostObj === null || !empty($hostObj->removed)) return;
    $ready = SWUResourceCount($player, readyOnly: true);
    $offer = [];
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Upgrade') === false) continue;
        if (!in_array($hostMz, SWUGetUpgradeValidTargets($player, $o->CardID), true)) continue;   // attachable to Kylo
        if (SWUComputePlayCost($player, $o, $hostObj) <= $ready) $offer[] = $mz;                   // affordable at full cost
    }
    if (empty($offer)) return;
    SWUQueueMayChooseTarget($player, $offer, "Play_an_upgrade_from_discard_on_Kylo?",
        "Choose_an_upgrade_to_play_on_Kylo", "LOF_001#1|{$hostUID}");
}
$customDQHandlers["LOF_001#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $upMz = $lastDecision ?? '';
    if ($upMz === '' || $upMz === '-' || $upMz === 'PASS') return;   // declined → stop the loop
    $up = str_contains($upMz, '-') ? GetZoneObject($upMz) : null;
    if ($up === null || !empty($up->removed)) return;
    $hostUID = intval($parts[0] ?? -1);
    $hostMz  = SWUFindMzByUID($hostUID);
    if ($hostMz === null) return;
    // pay full cost + attach onto Kylo; suppress the attach's After Action (the deploy owns it).
    _SWUFinalizeUpgradeAttach(intval($player), $up->CardID, $upMz, $hostMz, 0, false, false, true);
    _SWULof001Offer(intval($player), $hostUID);   // re-offer (any number)
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
        AddTurnEffect($lastDecision, 'SENTINEL^LOF_003');
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

// LOF_012 Rey (deployed) — When Deployed: You may discard your hand. If you do, draw 2 cards.
// Same shape as SOR_147 Black One; the deploy action owns the After Action (entry trigger).
$whenPlayedAbilities["LOF_012:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, 'Discard_your_hand_to_draw_2?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'LOF_012#1', 1);
};
$customDQHandlers["LOF_012#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID; $playerID = intval($player);
    foreach (GetHand(intval($player)) as $h) {
        if (!empty($h->removed)) continue;
        $cid = $h->CardID;
        $h->Remove();
        SWUAddToDiscard(intval($player), $cid, 'HAND');
    }
    DoDrawCard(intval($player), 2);
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

// LOF_016 Qui-Gon Jinn (deployed) — When this unit completes an attack (and survives): you may return a
// friendly non-leader unit to its owner's hand, then play a non-Villainy unit costing less than the
// returned unit from your hand for free. Same effect as the front Action; combat owns the After Action,
// so the deployed continuations (#2/#3) never call SWUAfterAction.
$onAttackEndAbilities["LOF_016:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_friendly_non-leader_unit?",
        "Return_a_friendly_non-leader_unit_to_hand", "LOF_016#2");
};
$customDQHandlers["LOF_016#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $returnedCost = intval(CardCost($o->CardID));
    SWUBounceUnit(intval($player), $lastDecision);
    $playables = [];
    foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 999) as $mz) {
        $h = GetZoneObject($mz); if ($h === null || !empty($h->removed)) continue;
        if (intval(CardCost($h->CardID)) < $returnedCost && strpos(CardAspect($h->CardID) ?? '', 'Villainy') === false) $playables[] = $mz;
    }
    if (empty($playables)) return;
    SWUQueueChooseTarget(intval($player), $playables, "Play_a_cheaper_non-Villainy_unit_for_free", "LOF_016#3");
};
$customDQHandlers["LOF_016#3"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, true, 0);   // ignoreCost = true (free)
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction(intval($player));
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
    if ($closeAction) SWUQueueAfterAction(intval($player));
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    AddTurnEffect($attackerMz, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, 'LAW_001'));
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction(intval($player));
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    SWUQueueAfterAction($player);
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
    if (SeatCountForGame() <= 2) {   // 2-player auto-resolve short-cuts (N-player always offers the picker)
        if (!$mine && !$theirs) { SWUAfterAction(intval($player)); return; }
        if ($mine && !$theirs) { _SWULaw018Mill(intval($player), intval($player), $aspect); return; }
        if ($theirs && !$mine) { _SWULaw018Mill(intval($player), $opp, $aspect); return; }
    }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@-&" . SWUDeckPickerLabels(intval($player)), 1, "Discard_from_which_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_018#1|" . $aspect, 1);
};
$customDQHandlers["LAW_018#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $aspect = $parts[0] ?? '';
    $owner = SWUDecodeDeckPick($lastDecision, intval($player)); // Your_deck→self, Opponent's_deck/P{n}_deck→that player
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

// ── ASH Phase 10 leaders ──────────────────────────────────────────────────────
// ASH_001 The Armorer — Action [Exhaust]: play an upgrade from your resources on a unit that entered play
// this phase (paying its cost). If you do, resource the top card of your deck. Eligible hosts = units with
// the SWU_PLAYED_UNIT_{uid} flag; resource-zone upgrades affordable + attachable to such a host.
$leaderAbilities["ASH_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)
                && GlobalEffectCount($player, 'SWU_PLAYED_UNIT_' . intval($o->UniqueID ?? 0)) > 0) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) { SWUAfterAction($player); return; }
    $ready     = SWUResourceCount($player, readyOnly: true);
    $resources = &GetResources($player);
    $targets   = [];
    $pos = 0;
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        $here = $pos; $pos++;
        $cid = $resources[$i]->CardID ?? '';
        if (strpos(CardType($cid) ?? '', 'Upgrade') === false) continue;
        if (SWUComputePlayCost($player, $resources[$i]) >= $ready) continue;   // need OTHER resources to pay
        $validHosts = SWUGetUpgradeValidTargets($player, $cid);
        $ok = false;
        foreach ($hosts as $h) { if (in_array($h, $validHosts, true)) { $ok = true; break; } }
        if ($ok) $targets[] = "myResources-{$here}";
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_an_upgrade_from_resources_on_a_unit_that_entered_this_phase", "ASH_001#0");
};
$customDQHandlers["ASH_001#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    $resObj = GetZoneObject($lastDecision);
    if ($resObj === null || !empty($resObj->removed)) { SWUAfterAction($player); return; }
    $cardID     = $resObj->CardID ?? '';
    $validHosts = SWUGetUpgradeValidTargets(intval($player), $cardID);
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)
                && GlobalEffectCount(intval($player), 'SWU_PLAYED_UNIT_' . intval($o->UniqueID ?? 0)) > 0
                && in_array($mz, $validHosts, true)) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_unit_that_entered_this_phase", "ASH_001#1|{$cardID}|{$lastDecision}");
};
$customDQHandlers["ASH_001#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $resMz  = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    if ($cardID === '' || !$hostMz || !str_contains($hostMz, '-')) { SWUAfterAction($player); return; }
    $host = GetZoneObject($hostMz);
    if ($host === null || !empty($host->removed)) { SWUAfterAction($player); return; }
    // Move the upgrade OUT of resources into hand first (so it can't pay for itself), then attach paying cost.
    $newHandMz = MZMove(intval($player), $resMz, "myHand");
    if ($newHandMz === null || $newHandMz === '-') { SWUAfterAction($player); return; }
    $handMz = '';
    foreach (ZoneSearch("myHand", null) as $mz) {
        $h = GetZoneObject($mz);
        if ($h !== null && empty($h->removed) && ($h->CardID ?? '') === $cardID) $handMz = $mz;
    }
    if ($handMz === '') { SWUAfterAction($player); return; }
    $attached = _SWUFinalizeUpgradeAttach(intval($player), $cardID, $handMz, $hostMz, 0, false, false, true);
    if ($attached) _SWUSec245Ramp(intval($player));   // "If you do, resource the top card of your deck."
    SWUAfterAction($player);
};

// ASH_001 The Armorer (deployed) — When Attack Ends: you may play an upgrade from your resources on
// a FRIENDLY unit (any, not just entered-this-phase). If you do, resource the top card of your deck.
// Combat owns the After Action (onAttackEnd), so the continuations never call SWUAfterAction.
$onAttackEndAbilities["ASH_001:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hosts = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    if (empty($hosts)) return;
    $ready     = SWUResourceCount(intval($player), readyOnly: true);
    $resources = &GetResources(intval($player));
    $targets   = []; $pos = 0;
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        $here = $pos; $pos++;
        $cid = $resources[$i]->CardID ?? '';
        if (strpos(CardType($cid) ?? '', 'Upgrade') === false) continue;
        if (SWUComputePlayCost(intval($player), $resources[$i]) >= $ready) continue;   // need OTHER resources to pay
        $validHosts = SWUGetUpgradeValidTargets(intval($player), $cid);
        $ok = false;
        foreach ($hosts as $h) { if (in_array($h, $validHosts, true)) { $ok = true; break; } }
        if ($ok) $targets[] = "myResources-{$here}";
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Play_an_upgrade_from_your_resources?",
        "Choose_an_upgrade_from_resources", "ASH_001#2");
};
$customDQHandlers["ASH_001#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || !str_contains($lastDecision, '-')) return;
    $resObj = GetZoneObject($lastDecision);
    if ($resObj === null || !empty($resObj->removed)) return;
    $cardID     = $resObj->CardID ?? '';
    $validHosts = SWUGetUpgradeValidTargets(intval($player), $cardID);
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { if (in_array($mz, $validHosts, true)) $hosts[] = $mz; }
    }
    if (empty($hosts)) return;
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_friendly_unit", "ASH_001#3|{$cardID}|{$lastDecision}");
};
$customDQHandlers["ASH_001#3"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $resMz  = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    if ($cardID === '' || !$hostMz || !str_contains($hostMz, '-')) return;
    $host = GetZoneObject($hostMz);
    if ($host === null || !empty($host->removed)) return;
    $newHandMz = MZMove(intval($player), $resMz, "myHand");
    if ($newHandMz === null || $newHandMz === '-') return;
    $handMz = '';
    foreach (ZoneSearch("myHand", null) as $mz) {
        $h = GetZoneObject($mz);
        if ($h !== null && empty($h->removed) && ($h->CardID ?? '') === $cardID) $handMz = $mz;
    }
    if ($handMz === '') return;
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $handMz, $hostMz, 0, false, false, true);
    // "If you do, resource the top card of your deck." Gate on the upgrade actually landing on the
    // host (the attach return is the trigger count, which is 0 for a vanilla upgrade — not a success flag).
    $host2 = GetZoneObject($hostMz);
    $attached = false;
    if ($host2 !== null) {
        foreach (GetUpgradesOnUnit($host2) as $u) {
            $uid = is_array($u) ? ($u['CardID'] ?? '') : ($u->CardID ?? '');
            if ($uid === $cardID) { $attached = true; break; }
        }
    }
    if ($attached) _SWUSec245Ramp(intval($player));
};

// ASH_006 Sabine Wren — Action [Exhaust]: an opponent gives 2 Advantage tokens to a unit they control. If
// they do, the next unit you play this phase gains Shielded (SWU_ASH006_SHIELDED_NEXT, applied at entry).
$leaderAbilities["ASH_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $opp = OtherPlayer($player);
    $sp = $playerID; $playerID = $opp;
    $oppUnits = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    $playerID = $sp;
    if (empty($oppUnits)) { SWUAfterAction($player); return; }   // opponent controls no unit → nothing happens
    if (count($oppUnits) === 1) {
        // Forced single target — resolve inline (no cross-player decision needed).
        $playerID = $opp;
        DoGiveAdvantageToken($opp, $oppUnits[0]);
        DoGiveAdvantageToken($opp, $oppUnits[0]);
        $playerID = $player;
        AddGlobalEffects($player, 'SWU_ASH006_SHIELDED_NEXT');   // "If they do"
        SWUAfterAction($player);
        return;
    }
    $playerID = $opp;   // multiple units → the opponent picks one of THEIR units
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $oppUnits), 1, tooltip: "Give_2_Advantage_to_a_unit_you_control");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "ASH_006#0|{$player}", 1);
    $playerID = $player;
};
$customDQHandlers["ASH_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);   // $player = the opponent who chose
    $leaderCtrl = intval($parts[0] ?? 0);
    if ($lastDecision && str_contains($lastDecision, '-')) {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            DoGiveAdvantageToken(intval($player), $lastDecision);
            DoGiveAdvantageToken(intval($player), $lastDecision);
            AddGlobalEffects($leaderCtrl, 'SWU_ASH006_SHIELDED_NEXT');   // "If they do"
        }
    }
    SWUAfterAction($leaderCtrl);
};

// ASH_017 Greef Karga — triggered: may exhaust → give an Advantage token to the just-played/created unit
// (its UID is passed via the trigger extra).
function Ash017Trigger($player, $uid): void {
    global $playerID; $playerID = intval($player);
    if ($uid <= 0 || SWUFindMzByUID($uid) === null) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Greef_to_give_that_unit_an_Advantage_token?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_017#0|{$uid}", 1);
}
$customDQHandlers["ASH_017#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_017' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) DoGiveAdvantageToken(intval($player), $mz); }
};

// ASH_018 Grogu — triggered (play a uq unit costing 4+): if Grogu is ready, you may deploy him.
function Ash018Trigger($player): void {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Deploy_Grogu?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_018#0", 1);
}
$customDQHandlers["ASH_018#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    SWUDeployLeader(intval($player), 'Unit');   // the ASH_018 gate branch only requires Grogu ready
};

// ASH_005 Luke Skywalker — "When a friendly unit's attack ends: you may exhaust this leader; if you do,
// heal 1 damage from that unit." Dispatched from the combat hook (DispatchTrigger case 'ASH_005').
function Ash005Trigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || !empty($self->removed)) return;   // attacker left play → nothing to heal
    if (intval($self->Damage ?? 0) <= 0) return;            // no damage on it → no benefit, skip the offer
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Luke_to_heal_1_from_that_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_005#0|{$mzID}", 1);
}
$customDQHandlers["ASH_005#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;   // declined → leader stays ready, no heal
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_005' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $mz = $parts[0] ?? '';
    if ($mz !== '' && str_contains($mz, '-')) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) OnHealUnit(intval($player), $mz, 1);
    }
};

// ASH_005 Luke Skywalker (DEPLOYED unit side) — "When a friendly unit's attack ends: Heal 2 damage from
// that unit or from your base." Mandatory heal (no "may"); the player chooses the source. Offer only the
// sources that actually carry damage — "that unit" (the attacker, $mzID) and/or "your base" — so the
// no-benefit case fizzles cleanly with no prompt (mirrors the undeployed side's Damage<=0 skip). Dispatched
// from the combat hook (DispatchTrigger case 'ASH_005#1').
function Ash005DeployedTrigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $targets = [];
    if ($mzID !== '' && str_contains($mzID, '-')) {
        $self = GetZoneObject($mzID);
        if ($self !== null && empty($self->removed) && intval($self->Damage ?? 0) > 0) $targets[] = $mzID;
    }
    $base = GetBase(intval($player));
    if (!empty($base) && empty($base[0]->removed) && intval($base[0]->Damage ?? 0) > 0) $targets[] = 'myBase-0';
    if (empty($targets)) return;   // neither the attacker nor the base is damaged → nothing to heal
    SWUQueueChooseTarget(intval($player), $targets, "Heal_2_from_that_unit_or_your_base", "HEAL_TARGET|2");
}


// ASH_002 Fennec Shand — Action [1 resource, Exhaust, exhaust a friendly unit]: play a unit from your hand
// (paying its cost). It enters play ready. Costs: 1 resource + leader exhaust (auto) + exhaust a friendly
// unit (chosen in ASH_002#0), then play a hand unit with $gForceEnterReady (ASH_002#1).
$leaderAbilities["ASH_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $handUnits = ZoneSearch("myHand", ["Unit", "Token Unit"]);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        $arr = GetZone($z);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if ($u !== null && empty($u->removed) && intval($u->Status) === 1) $ready[] = "{$z}-{$i}";
        }
    }
    if (empty($handUnits) || empty($ready)) { SWUAfterAction($player); return; }   // can't pay / nothing to play
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Exhaust_a_friendly_unit_(cost)", "ASH_002#0");
};
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

// ── Deployed Leader Unit "Action" abilities (leader-gaps.md Group E) ─────────
// Deployed leader Actions dispatch through SWUUnitAction → $unitAbilities[CardID]
// (NO fallback to $leaderAbilities). costKind 'none' = no self-exhaust, no framework
// resource cost (each closure pays its own cost). ASH_002/LOF_013/LOF_018 have a
// deployed effect identical to their front Action (only the front's self-Exhaust is
// dropped), so they reuse the front closure verbatim; the affordability gate for the
// Force-cost ones lives in SWUUnitActionAffordable (else UseTheForce no-ops → free play).
global $unitAbilities, $unitActionCostKind, $unitActionResourceCosts;

// ASH_002 Fennec Shand — Action [1 resource, exhaust a friendly unit]: play a unit ready.
$unitAbilities["ASH_002"]      = $leaderAbilities["ASH_002"];
$unitActionCostKind["ASH_002"] = 'none';

// LOF_013 Barriss Offee — Action [use the Force]: play an event from hand, costs 1 less.
$unitAbilities["LOF_013"]      = $leaderAbilities["LOF_013"];
$unitActionCostKind["LOF_013"] = 'none';

// LOF_018 Anakin Skywalker — Action [use the Force]: play a Villainy non-unit, ignoring aspect penalties.
$unitAbilities["LOF_018"]      = $leaderAbilities["LOF_018"];
$unitActionCostKind["LOF_018"] = 'none';

// SEC_007 Dryden Vos — Action [discard a card from your hand]: play a unit from your hand (paying its
// cost); it gains Ambush for this phase. The DEPLOYED side is broader than the front (discard ANY card,
// play ANY unit — no 6+/≤5 restriction), so it needs its own closure.
$unitActionCostKind["SEC_007"] = 'none';
$unitAbilities["SEC_007"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = ZoneSearch('myHand');
    if (empty($hand)) { SWUAfterAction(intval($player)); return; }   // no card to discard (the cost)
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand_(cost)", "SEC_007D");
};
$customDQHandlers["SEC_007D"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $mz);                              // pay the discard cost
    DecisionQueueController::CleanupRemovedCards();
    $ready = SWUResourceCount(intval($player), readyOnly: true);
    $units = [];
    foreach (ZoneSearch('myHand') as $hmz) {
        $u = GetZoneObject($hmz);
        if ($u === null || !empty($u->removed)) continue;
        if (stripos(CardType($u->CardID) ?? '', 'Unit') === false) continue;
        if (SWUComputePlayCost(intval($player), $u) > $ready) continue;
        $units[] = $hmz;
    }
    if (empty($units)) { SWUAfterAction(intval($player)); return; }   // nothing affordable to play
    SWUQueueChooseTarget(intval($player), $units, "Play_a_unit_from_your_hand_(it_gains_Ambush)", "SEC_007D1");
};
$customDQHandlers["SEC_007D1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_007';                               // the played unit gains Ambush this phase
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, false);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    SWUAfterAction(intval($player));
};

// ASH_007 Grand Admiral Sloane — Action [Exhaust]: choose one — give each ground unit, OR each space unit,
// Sentinel and Overwhelm for this phase. (Generic SENTINEL/OVERWHELM registry turn-effects, phase duration.)
$leaderAbilities["ASH_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1,
        tooltip: "Give_each_ground_OR_space_unit_Sentinel_and_Overwhelm_this_phase");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ASH_007#0", 1);
};
$customDQHandlers["ASH_007#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    foreach (["my{$arena}", "their{$arena}"] as $z) {   // "each ... unit" = both players' units in that arena
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) {
                AddTurnEffect($mz, 'SENTINEL^ASH_007');
                AddTurnEffect($mz, 'OVERWHELM^ASH_007');
            }
        }
    }
    SWUAfterAction($player);
};

// ASH_008 Moff Gideon — Action [Exhaust]: if a friendly Imperial unit was defeated this phase, play a unit
// from your hand. It costs 1 resource less. (SWU_IMPERIAL_DEFEATED gate; ActivateCard with discount 1.)
$leaderAbilities["ASH_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_IMPERIAL_DEFEATED') <= 0) { SWUAfterAction($player); return; }
    $handUnits = [];
    foreach (ZoneSearch("myHand", ["Unit", "Token Unit"]) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $handUnits[] = $mz;
    }
    if (empty($handUnits)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $handUnits, "Play_a_unit_from_your_hand_(costs_1_less)", "ASH_008#0");
};
$customDQHandlers["ASH_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, 1);   // play paying cost − 1
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    SWUAfterAction($player);
};

// ASH_013 Ezra Bridger — triggered (combat hook): may exhaust → give an Advantage token to a unit other
// than the attacker. ($parts[0] = attacker mzID, captured at trigger time.)
function Ash013Trigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Ezra_to_give_an_Advantage_token_to_a_different_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_013#0|{$mzID}", 1);
}
$customDQHandlers["ASH_013#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_013' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $attMz  = $parts[0] ?? '';
    $attObj = ($attMz && str_contains($attMz, '-')) ? GetZoneObject($attMz) : null;
    $attUID = $attObj ? intval($attObj->UniqueID ?? -1) : -1;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $attUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Advantage_token_to_a_different_unit", "GIVE_ADVANTAGE|1");
};

// ASH_014 The Mandalorian — "When you take the initiative: may pay 1 resource → draw a card." (Hooked in
// SWUTakeInitiative; this resolves the pay-and-draw on YES.)
$customDQHandlers["ASH_014#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    if (!SWUExhaustResources(intval($player), 1)) return;   // pay 1 resource
    DoDrawCard(intval($player), 1);
};

// ASH_015 Emperor Palpatine — Action [Exhaust]: choose an exhausted friendly unit; give it an Advantage
// token for each OTHER friendly unit.
$leaderAbilities["ASH_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 0) $targets[] = $mz;   // exhausted
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_Advantage_per_other_friendly_unit_to_an_exhausted_unit", "ASH_015#0");
};
$customDQHandlers["ASH_015#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    $self    = GetZoneObject($lastDecision);
    $selfUID = $self ? intval($self->UniqueID ?? -1) : -1;
    $n = 0;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $n++;
        }
    }
    for ($k = 0; $k < $n; $k++) DoGiveAdvantageToken(intval($player), $lastDecision);
    SWUAfterAction($player);
};

// ASH_016 Shin Hati — triggered (combat hook): may exhaust → exhaust a unit costing less than the combat
// damage dealt to a base this attack ($baseDmg passed via the trigger extra).
function Ash016Trigger($player, $mzID, $baseDmg): void {
    global $playerID; $playerID = intval($player);
    if ($baseDmg <= 0) return;   // no base damage → nothing costs "less than 0"
    $any = false;
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) < $baseDmg) { $any = true; break 2; }
        }
    }
    if (!$any) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Shin_to_exhaust_a_cheaper_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_016#0|{$baseDmg}", 1);
}
$customDQHandlers["ASH_016#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_016' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $baseDmg = intval($parts[0] ?? 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) < $baseDmg) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit_costing_less_than_{$baseDmg}", "EXHAUST_UNIT");
};

// ASH_009 Ahsoka Tano — Action [Exhaust]: choose a unit with less power than a friendly unit; +2/+0 this
// phase. Offer any unit (either side) whose power is below the highest friendly unit's power.
$leaderAbilities["ASH_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $maxF = -1;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $maxF = max($maxF, intval(ObjectCurrentPower($o)));
        }
    }
    if ($maxF < 0) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) < $maxF) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_+2/+0_to_a_unit_with_less_power_than_a_friendly_unit", "APPLY_PHASE_BUFF|2|0|ASH_009");
    SWUQueueAfterAction($player);
};

// ASH_010 Bo-Katan Kryze — Action [2 resources, Exhaust]: if you control a unit in each arena, create a
// Mandalorian token (ASH_T01).
$leaderAbilities["ASH_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 2)) { SWUAfterAction($player); return; }
    if (count(ZoneSearch('myGroundArena', AnyUnitFilter)) > 0 && count(ZoneSearch('mySpaceArena', AnyUnitFilter)) > 0) {
        SWUCreateUnitToken($player, 'ASH_T01');
    }
    SWUAfterAction($player);
};

// ASH_011 Cad Bane — Action [Exhaust]: deal 1 damage to a unit with 2 or more remaining HP.
$leaderAbilities["ASH_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) >= 2) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_to_a_unit_with_2+_remaining_HP", "DEAL_UNIT_DAMAGE|1");
    SWUQueueAfterAction($player);
};

// ASH_012 Vane — Action [Exhaust, defeat a friendly upgrade]: deal 2 damage to a base. The upgrade defeat
// is a COST (friendly-scoped, mandatory); the DefeatUpgThen continuation deals the 2 to a chosen base.
$leaderAbilities["ASH_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && count(GetUpgradesOnUnit($o)) > 0) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) { SWUAfterAction($player); return; }   // no friendly upgrade → can't pay the cost
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "ASH_012#0");
    if (count($hosts) === 1) DecisionQueueController::AddDecision($player, "PASSPARAMETER", $hosts[0], 1);
    else DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hosts), 1, "Defeat_a_friendly_upgrade_(cost)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEFEAT_UPGRADE", 1);
};
$customDQHandlers["ASH_012#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_damage_to_a_base", "DEAL_BASE_DAMAGE|2");
    SWUQueueAfterAction(intval($player));
};

// ASH_003 Baylan Skoll — Action [1 resource, Exhaust]: give a friendly unit +2/+2 for this phase if it's
// the only unit you control in its arena. Only offer units that are alone in their arena.
$leaderAbilities["ASH_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $ground = ZoneSearch('myGroundArena', AnyUnitFilter);
    $space  = ZoneSearch('mySpaceArena',  AnyUnitFilter);
    $targets = [];
    if (count($ground) === 1) $targets[] = $ground[0];   // the lone ground unit
    if (count($space)  === 1) $targets[] = $space[0];    // the lone space unit
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_+2/+2_to_a_unit_alone_in_its_arena", "APPLY_PHASE_BUFF|2|2|ASH_003");
    SWUQueueAfterAction($player);
};

// ASH_004 Grand Admiral Thrawn — Action [Exhaust]: attack with a unit. It gains Restore 2 for this attack
// if you control the same number of units as the defending player. (Restore heals on attack, so the grant
// resolves as "heal 2 from your base when this unit attacks" — applied as the attack begins.)
$leaderAbilities["ASH_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        $arr = GetZone($z);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if ($u === null || !empty($u->removed)) continue;
            if (intval($u->Status) === 1) $ready[] = "{$z}-{$i}";
        }
    }
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Choose_a_unit_to_attack_with", "ASH_004#0");
};
$customDQHandlers["ASH_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $attackerMz = $lastDecision ?? '';
    $attacker = (!empty($attackerMz) && str_contains($attackerMz, '-')) ? GetZoneObject($attackerMz) : null;
    if ($attacker === null || !empty($attacker->removed)) { SWUAfterAction($player); return; }
    // "Restore 2 for this attack if you control the same number of units as the defending player."
    if (count(GetUnitsInPlay(intval($player))) === count(GetUnitsInPlay(OtherPlayer(intval($player))))) {
        OnHealBase(intval($player), intval($player), 2);
    }
    BeginSWUAttack(intval($player), $attackerMz);   // owns the after-action
};

// ── SHD_006 Jabba the Hutt "His High Exaltedness" ───────────────────────────────
// Front Action [Exhaust]: Choose a unit. For this phase it gains "Bounty - The next unit you play
// this phase costs 1 resource less." The grant is a phase-duration BOUNTY turn-effect token whose
// dash param carries the discount (SHD_006-1 here; the deployed side grants -2). The custom reward
// is collected when the bountied unit is defeated/captured — see the granted-bounty snapshot in
// CollectWhenDefeatedTriggers + SWUCollectBounty (GameLogic.php). The deployed side (Epic deploy at
// 7+ resources = the standard threshold = printed cost 7; When-Deployed capture; the cost-2 Action)
// lives in CardDQHandlers.php. "Choose a unit" = ANY unit in any arena (you typically bounty an
// enemy so YOU — the opponent of its controller — collect it on defeat, CR 13.f).
function _SWUShd006AllUnits(int $player): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $out[] = $mz;
        }
    }
    return $out;
}
$leaderAbilities["SHD_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = _SWUShd006AllUnits($player);
    if (empty($targets)) { SWUAfterAction($player); return; } // defensive (affordability requires one)
    SWUQueueChooseTarget($player, $targets, "Choose_a_unit_to_give_a_Bounty", "SHD_006#0|1");
};

// ═══════════════════════════════════════════════════════════════════════════════
// SHD leaders — Batch 12.1 (SHD_002 Qi'ra, SHD_003 Finn, SHD_004 Rey)
// (Epic deploy is generic: threshold = leader's printed cost, handled in SWUDeployLeader.)
// ═══════════════════════════════════════════════════════════════════════════════

// ── SHD_004 Rey ────────────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: Give an Experience token to a unit with 2 or less power.
// Deployed: Restore 3 (keyword) + On Attack: You may give an Experience token to a unit with ≤2 power.
function _SWUShd004LowPowerTargets(int $player): array {
    $t = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) <= 2) $t[] = $mz;
        }
    }
    return $t;
}
$leaderAbilities["SHD_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = _SWUShd004LowPowerTargets($player);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_with_2_or_less_power", "GIVE_EXPERIENCE|1");
    SWUQueueAfterAction($player);
};
$onAttackAbilities["SHD_004:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWUShd004LowPowerTargets(intval($player));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_unit_with_2_or_less_power?", "Choose_a_unit", "GIVE_EXPERIENCE|1");
};

// ── SHD_003 Finn ───────────────────────────────────────────────────────────────
// Front Action [Exhaust]: Defeat a friendly upgrade on a unit. If you do, give a Shield token to it.
// Deployed On Attack: same, but "You may".
function _SWUShd003UpgradedFriendlies(int $player): array {
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && count(GetUpgradesOnUnit($o)) > 0) $hosts[] = $mz;
        }
    }
    return $hosts;
}
$leaderAbilities["SHD_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hosts = _SWUShd003UpgradedFriendlies($player);
    if (empty($hosts)) { SWUAfterAction($player); return; }
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "SHD_003#then");
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hosts), 1, tooltip: "Defeat_a_friendly_upgrade");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEFEAT_UPGRADE", 1);
    SWUQueueAfterAction($player);
};
$onAttackAbilities["SHD_003:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hosts = _SWUShd003UpgradedFriendlies(intval($player));
    if (empty($hosts)) return;
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "SHD_003#then");
    DecisionQueueController::AddDecision(intval($player), "MZMAYCHOOSE", implode("&", $hosts), 1, tooltip: "Defeat_a_friendly_upgrade?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEFEAT_UPGRADE", 1);
};
// Shared then-handler: shield the host whose upgrade was just defeated ($parts[0] = host mzID).
$customDQHandlers["SHD_003#then"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $hostMz = $parts[0] ?? '';
    if ($hostMz === '' || !str_contains($hostMz, '-')) return;
    $o = GetZoneObject($hostMz);
    if ($o === null || !empty($o->removed)) return;
    DoGiveShieldToken(intval($player), $hostMz);
};

// ── SHD_002 Qi'ra ──────────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: Deal 2 damage to a friendly unit. Then give a Shield token to it.
// Deployed: Grit (keyword) + When Deployed: Heal all damage from each unit. Then deal each unit damage
// equal to half its remaining HP, rounded down.
$leaderAbilities["SHD_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_2_to_a_friendly_unit_then_Shield_it", "SHD_002#0");
    SWUQueueAfterAction($player);
};
$customDQHandlers["SHD_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    $after = GetZoneObject($lastDecision);          // shield only if it survived the 2 damage
    if ($after !== null && empty($after->removed)) DoGiveShieldToken(intval($player), $lastDecision);
};
// When Deployed (deployed side): heal all, then deal each unit floor(remaining HP / 2).
$whenPlayedAbilities["SHD_002:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach ([1, 2] as $p) {
        foreach (array_merge(GetGroundArena($p) ?? [], GetSpaceArena($p) ?? []) as $u) {
            if (!empty($u->removed)) continue;
            $u->Damage = 0;   // heal all damage from each unit
        }
    }
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            $remHP = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
            $dmg   = intdiv(max(0, $remHP), 2);   // half its remaining HP, rounded down
            if ($dmg > 0) SWUDealDamageToUnit($mz, $dmg, intval($player));
        }
    }
};

// ── SHD_007 Moff Gideon ────────────────────────────────────────────────────────
// Front Action [Exhaust]: Attack with a unit that costs 3 or less. If it's attacking a unit, it gets
// +1/+0 for this attack (the vs-unit +1 lives in CombatLogic via the 'SHD_007_FRONT' marker).
// Deployed: Overwhelm (keyword, auto) + ≤3-cost friendly units get +1/+0 & Overwhelm while attacking an
// enemy unit (both combat-time, in CombatLogic).
$leaderAbilities["SHD_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $attackers = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1
                && intval(CardCost($o->CardID ?? '')) <= 3) $attackers[] = $mz;
        }
    }
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attackers, "Attack_with_a_unit_that_costs_3_or_less", "SHD_007#front");
};
$customDQHandlers["SHD_007#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect($lastDecision, 'SHD_007_FRONT');   // +1/+0 while attacking a unit (read in CombatLogic)
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── SHD_011 Kylo Ren ───────────────────────────────────────────────────────────
// Front Action [Exhaust, discard a card from your hand]: Give a unit +2/+0 for this phase.
// Deployed passive: "This unit gets -1/-0 for each card in your hand" (in ObjectCurrentPower).
$leaderAbilities["SHD_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }   // can't pay the discard cost
    SWUQueueChooseTarget($player, $hand, "Discard_a_card_from_your_hand_(cost)", "SHD_011#cost");
};
$customDQHandlers["SHD_011#cost"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ), fn($mz) => ($o = GetZoneObject($mz)) !== null && empty($o->removed)));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+2/+0_this_phase", "APPLY_PHASE_BUFF|2|0|SHD_011");
    SWUQueueAfterAction(intval($player));
};

// ── SHD_010 Bossk ──────────────────────────────────────────────────────────────
// Front Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may give it +1/+0 for this phase.
// Deployed: "When you collect a bounty: you may collect that bounty again. Once each round." (reactive)
$leaderAbilities["SHD_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (_SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasKeyword_Bounty($o)) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }   // no unit with a Bounty → action fizzles
    SWUQueueChooseTarget($player, $targets, "Deal_1_to_a_unit_with_a_Bounty", "SHD_010#front");
    SWUQueueAfterAction($player);
};
$customDQHandlers["SHD_010#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $uid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    if (SWUFindMzByUID($uid) === null) return;                 // defeated by the 1 → no unit left to buff
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip:"Give_it_+1/+0_for_this_phase?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_010#buff|{$uid}", 1);
};
$customDQHandlers["SHD_010#buff"] = function($player, $parts, $lastDecision) {
    if (($lastDecision ?? '') !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUApplyPhaseBuff($mz, 1, 0, 'SHD_010');   // +1/+0 for this phase
};

// ── SHD_009 Hunter ─────────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: Reveal a resource you control. If it shares a name with a friendly
// unique unit, return the resource to its owner's hand and put the top card of your deck into play as a
// resource. Deployed: Overwhelm (keyword, auto) + On Attack: same, but "You may".
function _SWUShd009ResourceTargets(int $player): array {
    $res = GetResources($player);
    $t = [];
    for ($i = 0, $pos = 0; $i < count($res); $i++) {
        if (!empty($res[$i]->removed)) continue;
        $t[] = "myResources-{$pos}"; $pos++;
    }
    return $t;
}
function _SWUShd009Resolve(int $player, string $resMz): void {
    global $playerID; $playerID = intval($player);
    $res = GetZoneObject($resMz);
    if ($res === null || !empty($res->removed)) return;
    $name = CardTitle($res->CardID ?? '');
    $match = false;
    foreach (GetUnitsInPlay($player) as $u) {
        if (empty($u->removed) && CardUnique($u->CardID ?? '') && CardTitle($u->CardID ?? '') === $name) { $match = true; break; }
    }
    if (!$match) return;   // reveal only; no name-match with a friendly unique unit → nothing happens
    if (!SWUReturnResourceToHand($player, $resMz)) return;
    DecisionQueueController::CleanupRemovedCards();
    $deck = &GetDeck($player);
    for ($i = 0; $i < count($deck); $i++) {
        if (!empty($deck[$i]->removed)) continue;
        $top = $deck[$i]->CardID; $deck[$i]->Remove();
        AddResources($player, $top, 0, $player, $player);   // enters exhausted
        AddGameLogEntry('RESOURCE', 'P' . $player . ' put a card into play as a resource');
        break;
    }
    DecisionQueueController::CleanupRemovedCards();
}
$leaderAbilities["SHD_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = _SWUShd009ResourceTargets($player);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Reveal_a_resource_you_control", "SHD_009#front");
    SWUQueueAfterAction($player);
};
$customDQHandlers["SHD_009#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    _SWUShd009Resolve(intval($player), $lastDecision);
};
$onAttackAbilities["SHD_009:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWUShd009ResourceTargets(intval($player));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Reveal_a_resource_you_control?", "Choose_a_resource", "SHD_009#front");
};

// ── SHD_013 Han Solo ───────────────────────────────────────────────────────────
// Front Action [Exhaust] / deployed Action: Play a unit from your hand. It costs 1 resource less. Deal 2
// damage to it. (Play-then-act-on-the-played-unit via the SEC_018 findable-marker pattern.)
function _SWUShd013Offer(int $player): bool {
    global $playerID; $playerID = $player;
    $ready = SWUResourceCount($player, readyOnly: true);
    $units = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        if (max(0, SWUComputePlayCost($player, $o) - 1) > $ready) continue;
        $units[] = $mz;
    }
    if (empty($units)) return false;
    SWUQueueChooseTarget($player, $units, "Play_a_unit_from_hand_(costs_1_less;_deal_2_to_it)", "SHD_013#play");
    return true;
}
$customDQHandlers["SHD_013#play"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $handMz = $lastDecision ?? '';
    if ($handMz === '' || !str_contains($handMz, '-')) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SHD_013';
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $handMz, false, 1);   // −1 discount; inner after-action neutralised
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                    && in_array('SHD_013', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }
    if ($newMz !== null) SWUDealDamageToUnit($newMz, 2, intval($player));
    SWUAfterAction(intval($player));
};
$leaderAbilities["SHD_013"] = function(int $player): void {
    if (!_SWUShd013Offer($player)) SWUAfterAction($player);
};
$unitActionCostKind["SHD_013"] = 'exhaust';
$unitAbilities["SHD_013"] = function($player, $mzID) {
    if (!_SWUShd013Offer(intval($player))) SWUAfterAction(intval($player));
};

// ── SHD_016 Fennec Shand ───────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust] / deployed Action: Play a unit that costs 4 or less from your hand
// (paying its cost). Give it Ambush for this phase. Deployed also: Saboteur (keyword, auto).
function _SWUShd016Offer(int $player): bool {
    global $playerID; $playerID = $player;
    $ready = SWUResourceCount($player, readyOnly: true);
    $units = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        if (intval(CardCost($o->CardID ?? '')) > 4) continue;
        if (SWUComputePlayCost($player, $o) > $ready) continue;
        $units[] = $mz;
    }
    if (empty($units)) return false;
    SWUQueueChooseTarget($player, $units, "Play_a_unit_costing_4_or_less_(it_gains_Ambush)", "SHD_016#play");
    return true;
}
$customDQHandlers["SHD_016#play"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($o === null || !empty($o->removed)) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_007';   // reuse the "played unit gains Ambush this phase" marker
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, false);   // pays the unit's cost
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    SWUAfterAction(intval($player));
};
$leaderAbilities["SHD_016"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    if (!_SWUShd016Offer($player)) SWUAfterAction($player);
};
$leaderActionResourceCosts["SHD_016"] = 1;
$unitActionCostKind["SHD_016"] = 'exhaust';
$unitAbilities["SHD_016"] = function($player, $mzID) {
    if (!_SWUShd016Offer(intval($player))) SWUAfterAction(intval($player));
};

// ── SHD_017 Lando Calrissian ────────────────────────────────────────────────────
// Front Action [Exhaust] / deployed Action (once each round): "Play a card using Smuggle. It costs 2
// resources less. Defeat a resource you own and control." Ruling (CR — a leader ability resolves fully in
// sequence): the resource is defeated AFTER the Smuggled card's slot is replaced but BEFORE its When Played
// — enforced by SWUSmuggleResource's deferHandler path. Scope: offers smugglable UNIT resources.
function _SWUShd017HasTarget(int $player): bool {
    $ready = SWUResourceCount($player, readyOnly: true);
    foreach (GetResources($player) as $r) {
        if (!empty($r->removed) || SWUIsCreditToken($r->CardID ?? '')) continue;
        $cid = $r->CardID ?? '';
        if (stripos(CardType($cid) ?? '', 'Unit') === false) continue;
        $c = GetEffectiveSmuggleCost($player, $cid);
        if ($c >= 0 && $ready >= max(0, $c - 2)) return true;
    }
    return false;
}
function _SWUShd017Offer(int $player): bool {
    global $playerID; $playerID = $player;
    $ready   = SWUResourceCount($player, readyOnly: true);
    $specs   = [];
    $logical = 0;
    foreach (GetResources($player) as $r) {
        if (!empty($r->removed) || SWUIsCreditToken($r->CardID ?? '')) continue;
        $cid = $r->CardID ?? '';
        if (stripos(CardType($cid) ?? '', 'Unit') !== false) {
            $c = GetEffectiveSmuggleCost($player, $cid);
            if ($c >= 0 && $ready >= max(0, $c - 2)) $specs[] = "myResources-{$logical}";
        }
        $logical++;   // logical index counts every non-credit non-removed resource (matches SWUSmuggleResource)
    }
    if (empty($specs)) return false;
    SWUQueueChooseTarget($player, $specs, "Play_a_card_using_Smuggle_(costs_2_less)", "SHD_017#smuggle");
    return true;
}
$customDQHandlers["SHD_017#smuggle"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || $mz === '-' || $mz === 'PASS') { SWUAfterAction(intval($player)); return; }
    $idx = intval(substr($mz, strrpos($mz, '-') + 1));
    SWUSmuggleResource(intval($player), $idx, 2, 'SHD_017#defeat');   // -2 cost; defer entry until after resource-defeat
};
// Deferred: the Smuggled slot is replaced; now defeat a resource you own and control, THEN fire the
// Smuggled card's When Played (via _SWUShd017FireDeferred → _SWUSmuggleFireEntry).
$customDQHandlers["SHD_017#defeat"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $specs = [];
    foreach (GetResources(intval($player)) as $i => $r) {
        if (!empty($r->removed) || SWUIsCreditToken($r->CardID ?? '')) continue;
        // "you own AND control" — resources in your zone are yours unless STOLEN (Owner explicitly the
        // enemy). Owner 0/unset defaults to you (the documented zone-Owner gotcha).
        $owner = intval($r->Owner ?? 0);
        if ($owner > 0 && $owner !== intval($player)) continue;
        $specs[] = "myResources-{$i}";
    }
    if (empty($specs)) { _SWUShd017FireDeferred(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $specs, "Defeat_a_resource_you_own_and_control", "SHD_017#resolve");
};
$customDQHandlers["SHD_017#resolve"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDefeatResource(intval($player), $lastDecision);
    _SWUShd017FireDeferred(intval($player));
};
function _SWUShd017FireDeferred(int $player): void {
    $d = $GLOBALS['gSmuggleDeferred'] ?? null;
    if ($d === null) { SWUAfterAction($player); return; }
    unset($GLOBALS['gSmuggleDeferred']);
    _SWUSmuggleFireEntry(intval($d['player']), $d['cardID'], $d['mz'], $d['arena']);
}
$leaderAbilities["SHD_017"] = function(int $player): void {   // front: [Exhaust] (leader exhausted by SWULeaderAction)
    global $playerID; $playerID = $player;
    if (!_SWUShd017Offer($player)) SWUAfterAction($player);
};
$unitActionCostKind["SHD_017"] = 'none';   // deployed Action: no exhaust; gated once-per-round
$unitAbilities["SHD_017"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_SHD017_USED');   // consume the once-per-round use
    if (!_SWUShd017Offer(intval($player))) SWUAfterAction(intval($player));
};

// ── TS26 leaders ────────────────────────────────────────────────────────────────
// Collect the mzIDs of friendly units that entered play this phase (SWU_ENTERED_PHASE_{uid}); optionally
// exclude one UID. Used by TS26_002 Anakin and TS26_004 Padmé (both sides).
function _SWUEnteredThisPhaseUnits(int $player, int $excludeUID = -1): array {
    global $playerID; $playerID = intval($player);
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            $uid = intval($o->UniqueID ?? -1);
            if ($uid === $excludeUID) continue;
            if (GlobalEffectCount(intval($player), 'SWU_ENTERED_PHASE_' . $uid) > 0) $out[] = $mz;
        }
    }
    return $out;
}

// TS26_006 Rex (front) — Action [Exhaust, ready an exhausted enemy unit]: the next event you play this
// phase costs 1 resource less. (Deployed: On Attack, may ready an exhausted enemy → next event -2.)
$leaderAbilities["TS26_006"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $enemy = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) $enemy[] = $mz;  // exhausted
        }
    }
    if (empty($enemy)) { SWUAfterAction(intval($player)); return; }   // cost can't be paid (guarded in affordability)
    SWUQueueChooseTarget(intval($player), $enemy, "Ready_an_exhausted_enemy_unit_(next_event_-1)", "TS26_006#0|1|1");
};

// TS26_002 Anakin Skywalker (front) — Action [Exhaust]: if 2+ friendly units entered play this phase,
// give a Shield token to 1 of them. (Deployed: Sentinel auto + OnAttack shield another entered unit.)
$leaderAbilities["TS26_002"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $entered = _SWUEnteredThisPhaseUnits(intval($player));
    if (count($entered) < 2) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $entered, "Give_a_Shield_to_a_unit_that_entered_this_phase", "GIVE_SHIELD");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// TS26_004 Padmé Amidala (front) — Action [Exhaust]: if 2+ friendly units entered play this phase, attack
// with 1 of them (even if exhausted); it can't attack bases. (Deployed: When Attack Ends, may attack with
// another entered unit.)
$leaderAbilities["TS26_004"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $entered = _SWUEnteredThisPhaseUnits(intval($player));
    if (count($entered) < 2) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $entered, "Attack_with_a_unit_that_entered_this_phase_(no_bases)", "TS26_004#0");
};
$customDQHandlers["TS26_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    BeginSWUAttack(intval($player), $lastDecision, true);   // noBases; combat owns the after-action
};

// TS26_003 Maul (front) — Action [Exhaust]: choose a unit; if it has more different keywords than
// Experience tokens on it, give an Experience token to it and deal 1 damage to it. (Deployed side: same
// effect on When Deployed / On Attack — shared TS26_003#0 handler.)
$leaderAbilities["TS26_003"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) { SWUAfterAction(intval($player)); return; }
    SWUQueueMayChooseTarget(intval($player), $tg, "Choose_a_unit_(+1_Exp_and_1_damage_if_more_keywords_than_Experience)?", "Choose_a_unit", "TS26_003#0|1");
};

// TS26_007 Asajj Ventress (front) — Action [Exhaust]: attack with a token unit; it gets +1/+0 for this
// attack. (Deployed side: Hidden auto + a +2/+0 passive while you've attacked with a token this phase.)
$leaderAbilities["TS26_007"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $tokens = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Token Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $tokens[] = $mz;
        }
    }
    if (empty($tokens)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $tokens, "Attack_with_a_token_unit_(+1/+0)", "TS26_007#0");
};

// TS26_001 Count Dooku (front) — Action [Exhaust]: choose 2 players; they each heal 1 from their base and
// create a Battle Droid token. (2-player: both players. Deployed side: Restore 2 auto + OnAttack create 2.)
$leaderAbilities["TS26_001"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    OnHealBase(intval($player), intval($player), 1);
    SWUCreateUnitToken(intval($player), 'TS26_T01');
    OnHealBase(intval($player), $opp, 1);
    SWUCreateUnitToken($opp, 'TS26_T01');
    SWUAfterAction(intval($player));
};

?>
