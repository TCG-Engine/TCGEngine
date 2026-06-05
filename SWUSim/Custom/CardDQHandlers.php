<?php
// SWU card-specific DQ handlers and trigger ability closures.
// This file owns all ability arrays. The generator produces Has*Ability() checks;
// implementations live here and survive generator runs.

global $whenPlayedUsingSmuggleAbilities, $whenPlayedAsUpgradeAbilities;
global $whenPlayedAbilities, $whenDefeatedAbilities;
global $onAttackAbilities, $onDefenseAbilities, $onAttackEndAbilities, $onAttackEndFromUpgradeAbilities, $onAttackedFromUpgradeAbilities;
global $unitAbilities, $unitActionResourceCosts, $unitActionCostKind;
global $onAttachedAbilities;

$whenPlayedUsingSmuggleAbilities = [];
$whenPlayedAsUpgradeAbilities    = [];
$whenPlayedAbilities             = [];
$whenDefeatedAbilities           = [];
$onAttackAbilities               = [];
$onDefenseAbilities              = [];
$onAttackEndAbilities            = [];
$onAttachedAbilities             = [];

// ── SOR_022 Energy Conversion Lab — Base Epic Action ────────────────────────
// Resolves the unit-selection MZCHOOSE queued by BaseAbilities.php.
// Injects AMBUSH into $gPendingEntryEffects keyed by the next UniqueID so that
// ActivateCard picks it up before running keyword checks (Ambush, Shielded ordering).
$customDQHandlers["SOR_022#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gUniqueIDCounter, $gPendingEntryEffects;
    $savedPID = $playerID;
    $playerID = intval($player);
    $chosen   = $lastDecision; // mzID from MZCHOOSE
    $obj      = GetZoneObject($chosen);
    if ($obj === null) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }
    // Tag the next UniqueID with the SOR_022 grant token (registry: Ambush, this phase) so
    // ActivateCard applies it on entry. The CardID token gives the Active Effects UI its provenance.
    $nextUID = intval($gUniqueIDCounter) + 1;
    $gPendingEntryEffects[$nextUID] = ['SOR_022'];
    // ActivateCard pays normal cost (printed + aspect penalty) and handles all
    // keyword checks, Shielded/Ambush ordering, WhenPlayed triggers, and AfterAction.
    ActivateCard($player, $chosen, false);
    $playerID = $savedPID;
};

// ── SOR_019 Security Complex — Base Epic Action ─────────────────────────────
// Resolves the shield-target choice queued by BaseAbilities.php.
$customDQHandlers["SOR_019#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision && $lastDecision !== "-") {
        GiveShieldToken($player, $lastDecision);
    }
    SWUAfterAction($player);
};

// ── SOR_005 Luke Skywalker — leader ability DQ handler ──────────────────────
$customDQHandlers["SOR_005#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-') {
        SWUAfterAction(intval($player));
        return;
    }
    GiveShieldToken(intval($player), $lastDecision);
    SWUAfterAction(intval($player));
};

// ── SOR_005 Luke Skywalker — Leader Unit On Attack ──────────────────────────
// On Attack: You may give a shield token to another unit. Single MZMAYCHOOSE via the
// helper; GIVE_SHIELD no-ops on a '-' decline. "Another" excludes the attacker's own mzID.
$onAttackAbilities["SOR_005:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ), fn($mz) => $mz !== $mzID));
    SWUQueueMayChooseTarget(intval($player), $targets,
        'Give_a_shield_to_another_unit?', 'Choose_a_unit_to_give_a_shield_to', 'GIVE_SHIELD', 0);
};

// ── Batch 4.3: shield / heal triggers ───────────────────────────────────────
// Shared helper: all non-removed units of either player matching $pred(object),
// excluding the unit with UniqueID $excludeUID ("another …"). Caller sets $playerID.
function _SWUCollectUnits(int $excludeUID, callable $pred): array {
    $out = [];
    foreach (array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('mySpaceArena',     AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? -2) === $excludeUID) continue;
        if ($pred($o)) $out[] = $mz;
    }
    return $out;
}

// SOR_050 The Ghost — When Played/On Attack: You may give a Shield token to another
// SPECTRE unit. Shared closure; $mzID is The Ghost's own mzID (excluded — "another").
$whenPlayedAbilities["SOR_050:0"] =
$onAttackAbilities["SOR_050:0"]   = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = $self ? intval($self->UniqueID ?? -1) : -1;
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits($selfUID, fn($o) => HasTrait($o->CardID, 'Spectre')),
        'Give_a_Shield_to_another_Spectre_unit?', 'Choose_a_Spectre_unit_to_Shield', 'GIVE_SHIELD');
};

// SOR_059 2-1B Surgical Droid — On Attack: You may heal 2 damage from another unit.
$onAttackAbilities["SOR_059:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = $self ? intval($self->UniqueID ?? -1) : -1;
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits($selfUID, fn($o) => true),
        'Heal_2_damage_from_another_unit?', 'Choose_a_unit_to_heal_2', 'HEAL_TARGET|2');
};

// SOR_060 Distant Patroller — When Defeated: You may give a Shield token to a [Vigilance] unit.
$whenDefeatedAbilities["SOR_060:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits(-1, fn($o) => strpos(CardAspect($o->CardID) ?? '', 'Vigilance') !== false),
        'Give_a_Shield_to_a_Vigilance_unit?', 'Choose_a_Vigilance_unit_to_Shield', 'GIVE_SHIELD');
};

// SOR_068 Cargo Juggernaut — When Played: If you control another [Vigilance] unit, heal 4
// damage from your base. Automatic (not optional); $mzID is the Juggernaut's own mzID.
// JTL_143 Devastator — When Played: deal 4 indirect damage to each opponent. (Its passive "You
// assign all indirect damage you deal to opponents" lives in SWUIndirectAssignToOpponentSources,
// so the controller does the assigning — applied automatically by the funnel.)
$whenPlayedAbilities["JTL_143:0"] = function($player, $mzID) {
    SWUDealIndirectToEachOpponent(intval($player), 4);
};

$whenPlayedAbilities["SOR_068:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = $self ? intval($self->UniqueID ?? -1) : -1;
    $others  = [];
    foreach (array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? -2) === $selfUID) continue;                      // "another"
        if (strpos(CardAspect($o->CardID) ?? '', 'Vigilance') !== false) { $others[] = $mz; break; }
    }
    if (empty($others)) return;
    DecisionQueueController::AddDecision($player, 'PASSPARAMETER', 'myBase-0', 1);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HEAL_TARGET|4', 1);
};

// SOR_053 Luke's Lightsaber — When Played (as upgrade): If attached unit is Luke Skywalker,
// heal all damage from him and give him a Shield token. $mzID is the HOST unit's mzID
// (CollectWhenPlayedAsUpgradeTriggers falls back to the WhenPlayed window for non-pilot upgrades).
$whenPlayedAbilities["SOR_053:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    if (CardTitle($host->CardID) !== 'Luke Skywalker') return;
    OnHealUnit(intval($player), $mzID, 99);   // heal ALL damage (clamped at 0)
    GiveShieldToken(intval($player), $mzID);
};

// ── SOR_010 Darth Vader — leader ability DQ handler ─────────────────────────
$customDQHandlers["SOR_010#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUDealDamageToBase(1, GetOpponent(intval($player)));
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

// ── SHD_012 Bo-Katan Kryze — leader ability DQ handler ──────────────────────
$customDQHandlers["SHD_012#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') {
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
    $attackerUID = $attackerObj ? intval($attackerObj->UniqueID ?? 0) : 0;

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
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return; // declined → skip whole ability
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

// ── SOR_014 Sabine Wren — Leader Unit On Attack ─────────────────────────────
// On Attack: Deal 1 damage to each enemy base.
// Non-interactive — fires and resolves automatically through the DQ drain.
$onAttackAbilities["SOR_014:0"] = function($player) {
    SWUDealDamageToBase(1, GetOpponent($player));
};

// ── SOR_244 Snowspeeder — Unit On Attack ────────────────────────────────────
// On Attack: Exhaust an enemy Vehicle ground unit. Interactive — choose one
// valid target; resolves to no-op when there is no enemy Vehicle ground unit.
// $playerID is already $player (set by OnAttackTrigger / EffectStack dispatch).
$onAttackAbilities["SOR_244:0"] = function($player) {
    $targets = array_values(array_filter(
        ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"]),
        function($mz) {
            $u = GetZoneObject($mz);
            return $u !== null && !($u->removed ?? false) && HasTrait($u->CardID, 'Vehicle');
        }
    ));
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 0,
        tooltip:"Exhaust_an_enemy_Vehicle_ground_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_244#0", 0);
};
$customDQHandlers["SOR_244#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '') return;
    global $playerID;
    $saved = $playerID;
    $playerID = intval($player);
    $u = GetZoneObject($lastDecision);
    if ($u !== null && !($u->removed ?? false)) $u->Status = 0;
    $playerID = $saved;
};

// ── JTL_172 Twin Laser Turret — On Attack (granted via upgrade) ──────────────
// Attached unit gains: "On Attack: Deal 1 damage to each of up to 2 units in
// this arena." Implemented pragmatically as a single-target choice (deal 1 to
// the chosen unit). $mzID is the host unit's arena mzID. $playerID is $player.
$onAttackAbilities["JTL_172:0"] = function($player, $mzID) {
    $unitObj = GetZoneObject($mzID);
    if ($unitObj === null || ($unitObj->removed ?? false)) return;
    $location = $unitObj->Location ?? 'GroundArena';
    $prefix   = (strpos($location, 'Space') !== false) ? 'Space' : 'Ground';
    $targets  = array_merge(
        ZoneSearch("my{$prefix}Arena",    ["Unit", "Leader Unit"]),
        ZoneSearch("their{$prefix}Arena", ["Unit", "Leader Unit"])
    );
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 0,
        tooltip:"Deal_1_damage_to_a_unit_in_this_arena");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEAL_UNIT_DAMAGE|1", 0);
};

// ── JTL_001 Asajj Ventress (leader action continuation) ─────────────────────
// $lastDecision = the chosen friendly unit. Deal 1 to it, then "if you do" deal 1 to an enemy unit in
// the SAME arena. The arena is read off the friendly mzID; the enemy half is a mandatory choose over
// that arena's enemy units (DEAL_UNIT_DAMAGE|1), followed by SWU_AFTER_ACTION to close the action.
$customDQHandlers["JTL_001#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $enemyZone = (strpos($lastDecision, 'Space') !== false) ? 'theirSpaceArena' : 'theirGroundArena';
    $enemies = ZoneSearch($enemyZone, AnyUnitFilter);
    if (empty($enemies)) { SWUAfterAction(intval($player)); return; } // no same-arena enemy → done
    SWUQueueChooseTarget(intval($player), $enemies,
        "Deal_1_damage_to_an_enemy_unit_in_the_same_arena", "DEAL_UNIT_DAMAGE|1");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SWU_AFTER_ACTION', 1);
};

// ── JTL_003 Lando Calrissian (leader action: play unit, then conditional Shield) ─────────────────
// $lastDecision = the chosen hand unit. Queue the post-play Shield check FIRST (block 1, runs before
// the play's FINISH_PLAY_CARD at block 10), then play the unit at full cost — ActivateCard owns the
// end-of-action. The "JTL_003#1" step runs once the unit is on the board.
$customDQHandlers["JTL_003#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_003#1', 1);
    ActivateCard(intval($player), $lastDecision, false, 0);
};

// If, after the play, P1 controls both a ground unit and a space unit, give a Shield token to a unit
// (any unit). Mandatory choose; GIVE_SHIELD doesn't close the action (FINISH_PLAY_CARD does).
$customDQHandlers["JTL_003#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hasGround = !empty(ZoneSearch("myGroundArena", AnyUnitFilter));
    $hasSpace  = !empty(ZoneSearch("mySpaceArena",  AnyUnitFilter));
    if (!$hasGround || !$hasSpace) return; // condition not met → no Shield
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_Shield_token_to_a_unit", "GIVE_SHIELD");
};

// ── JTL_004 Rose Tico (deployed leader unit) — On Attack: You may heal 2 damage from a Vehicle unit ──
// Any Vehicle (no "attacked this phase" restriction on the deployed side). On-Attack triggers don't
// close the action (combat owns it), so the may-decline handler simply no-ops.
$onAttackAbilities["JTL_004:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
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
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_heal_2_from_a_Vehicle_unit", "Heal_2_from_a_Vehicle_unit", "HEAL_TARGET|2");
};

// ── JTL_005 Admiral Piett (leader action continuation) ──────────────────────
// $lastDecision = the chosen Capital Ship. Play it at a 1-resource discount; ActivateCard owns the
// end-of-action.
$customDQHandlers["JTL_005#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    ActivateCard(intval($player), $lastDecision, false, 1);
};

// ── JTL_007 Admiral Holdo (deployed leader unit) — On Attack: may buff ANOTHER Resistance unit ──
// "You may give another Resistance unit (or a unit with a Resistance upgrade) +2/+2 for this phase."
// $mzID is Holdo's mzID; exclude her by UniqueID. On-Attack doesn't close the action (combat owns it).
$onAttackAbilities["JTL_007:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || intval($o->UniqueID ?? 0) === $selfUid) continue; // "another" excludes Holdo
        if (_SWUIsResistanceTarget($o)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_another_Resistance_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_007");
};

// ── JTL_008 Wedge Antilles (leader action continuation) ─────────────────────
// $lastDecision = the chosen hand card. Initiate the Piloting play (the SWU_PILOT_DISCOUNT flag set in
// the leader ability is honored by SWUComputePilotCost and consumed at charge). SWUQueuePilotVehiclePick
// owns the vehicle pick + attach + after-action; on a fizzle remove the lingering discount flag.
$customDQHandlers["JTL_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
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

// ── JTL_010 Captain Phasma (deployed leader unit) — On Attack ───────────────
// "If you played another First Order card this phase, you may deal 1 damage to a unit. If you do, deal
// 1 damage to a base." Gated on SWU_PLAYED_FO. The may-choose routes to the JTL_010 continuation, which
// deals 1 to the chosen unit then offers the base.
$onAttackAbilities["JTL_010:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_FO') <= 0) return;
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_1_damage_to_a_unit", "Deal_1_damage_to_a_unit", "JTL_010#0");
};

// Deploy continuation: deal 1 to the chosen unit ($lastDecision), then "if you do" deal 1 to a base.
$customDQHandlers["JTL_010#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'],
        "Deal_1_damage_to_a_base", "DEAL_BASE_DAMAGE|1");
};

// ── JTL_011 Major Vonreg (leader action: play Vehicle, then buff ANOTHER unit) ───────────────────
// $lastDecision = the chosen Vehicle hand card. Snapshot in-play unit UIDs, play the Vehicle (full
// cost; ActivateCard owns the after-action), then queue the +1/+0 step at block 1 carrying the
// newly-played unit's UID so "another unit" excludes it.
$customDQHandlers["JTL_011#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $before = [];
    foreach (GetField(intval($player)) as $u) {
        if ($u !== null && empty($u->removed)) $before[] = intval($u->UniqueID ?? 0);
    }
    ActivateCard(intval($player), $lastDecision, false, 0);
    $newUid = 0;
    foreach (GetField(intval($player)) as $u) {
        if ($u === null || !empty($u->removed)) continue;
        $uid = intval($u->UniqueID ?? 0);
        if (!in_array($uid, $before, true)) { $newUid = $uid; break; }
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_011#1|{$newUid}", 1);
};

// Buff step: give ANOTHER unit (any unit except the just-played one, $parts[0] = its UID) +1/+0 this
// phase. Mandatory choose; no after-action (the play's FINISH_PLAY_CARD owns it).
$customDQHandlers["JTL_011#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $excludeUid = intval($parts[0] ?? 0);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? 0) === $excludeUid) continue; // "another" excludes the played unit
        $targets[] = $mz;
    }
    if (empty($targets)) return; // no other unit → buff fizzles
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_another_unit_+1/+0_this_phase", "APPLY_PHASE_BUFF|1|0|JTL_011");
};

// ── JTL_014 Admiral Trench (leader action: discard a 3+ cost card, then draw) ────────────────────
// $lastDecision = the chosen hand card (already filtered to cost >= 3). Discard it and draw 1; the
// leader ability queued SWU_AFTER_ACTION after this to close the action.
$customDQHandlers["JTL_014#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    DoDiscardCard(intval($player), $lastDecision);
    DoDrawCard(intval($player), 1);
};

// ── JTL_015 Rio Durant (leader action: attack with a space unit, +1/+0 + Saboteur this attack) ──────
// $lastDecision = the chosen space unit. Grant the per-attack effects then begin the attack
// (BeginSWUAttack owns the combat continuation / after-action).
$customDQHandlers["JTL_015#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, 'JTL_015');          // Saboteur for this attack (registry, attack duration)
    SWUAddAttackPowerBonus($lastDecision, 1);         // +1/+0 for this attack
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_016 Admiral Ackbar — leader action AND deploy On Attack: exhaust a non-leader unit → its
// controller creates an X-Wing token. Shared continuation (no after-action; the leader action queues
// SWU_AFTER_ACTION separately, and On Attack is owned by combat). No-ops on a '-' may-decline.
$customDQHandlers["JTL_016#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $controller = intval($obj->Controller ?? $player);
    OnExhaustCard(intval($player), $lastDecision);
    SWUCreateUnitToken($controller, 'JTL_T02'); // X-Wing (Space, 2/2)
};

// Deploy On Attack: "You may exhaust a unit. If you do, its controller creates an X-Wing token."
$onAttackAbilities["JTL_016:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_exhaust_a_unit", "Exhaust_a_unit_(its_controller_creates_an_X-Wing)", "JTL_016#0");
};

// ── JTL_018 Kazuda Xiono — leader action continuation: apply the lose-all-abilities token to the chosen
// friendly unit, then take an EXTRA action (no turn swap). The token expires at round end like JTL_244.
$customDQHandlers["JTL_018#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== '' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'JTL_018');
    }
    SWUAfterActionExtra(intval($player)); // "Take an extra action after this one."
};

// Deploy On Attack: "Choose any number of friendly units. They lose all abilities for this round."
// (Combat owns the after-action — no SWU_AFTER_ACTION here.)
$onAttackAbilities["JTL_018:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . count($targets) . "|" . implode("&", $targets), 1,
        tooltip: "Choose_friendly_units_to_lose_all_abilities_this_round");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_018#1", 1);
};

$customDQHandlers["JTL_018#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '') return;
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'JTL_018');
    }
};

// ── JTL_017 Han Solo (leader action: reveal top, attack; +1/+0 if different odd costs) ──────────────
// $parts[0] = the revealed card's cost. $lastDecision = the chosen attacker. If the revealed cost and
// the attacker's cost are BOTH odd and DIFFERENT, grant +1/+0 for this attack; then begin the attack.
$customDQHandlers["JTL_017#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $revealedCost = intval($parts[0] ?? -1);
    $obj = GetZoneObject($lastDecision);
    $unitCost = ($obj !== null) ? intval(CardCost($obj->CardID)) : -1;
    $bothOdd  = ($revealedCost % 2 !== 0) && ($unitCost % 2 !== 0) && $revealedCost >= 0 && $unitCost >= 0;
    if ($bothOdd && $revealedCost !== $unitCost) {
        SWUAddAttackPowerBonus($lastDecision, 1);
    }
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_037 Banshee — On Attack: You may deal damage to a unit equal to the damage on this unit. ─────
$onAttackAbilities["JTL_037:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || !empty($self->removed)) return;
    $amount = intval($self->Damage ?? 0);
    if ($amount <= 0) return; // no damage on this unit → nothing to deal
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_equal_to_damage_on_this_unit", "Deal_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $amount);
};

// ── JTL_051 Red Squadron X-Wing — When Played: You may deal 2 damage to this unit. If you do, draw. ──
$whenPlayedAbilities["JTL_051:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1,
        tooltip: "Deal_2_damage_to_this_unit_to_draw_a_card?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_051#0|" . $mzID, 1);
};
$customDQHandlers["JTL_051#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $mz = $parts[0] ?? '';
    if ($mz !== '') SWUDealDamageToUnit($mz, 2, intval($player));
    DoDrawCard(intval($player), 1);
};

// ── JTL_102 Resistance Blue Squadron — When Played: You may deal damage to a unit equal to the number
// of friendly space units (including itself, which has just entered). ───────────────────────────────
$whenPlayedAbilities["JTL_102:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $amount = count(ZoneSearch("mySpaceArena", AnyUnitFilter));
    if ($amount <= 0) return;
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_to_a_unit", "Deal_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $amount);
};

// ── JTL_140 IG-2000 — Overwhelm (auto-wired) + When Played: Deal 1 damage to each of up to 3 units. ──
$whenPlayedAbilities["JTL_140:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    $effectiveMax = min(3, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $effectiveMax . "|" . implode("&", $targets), 1, tooltip: "Deal_1_damage_to_each_of_up_to_3_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_140#0", 1);
};
$customDQHandlers["JTL_140#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    // Snapshot UIDs of the picks (cap at 3), then deal 1 to each (AOE-safe: a defeat shifts indices).
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    $uids = array_slice($uids, 0, 3);
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUDealDamageToUnit($mz, 1, intval($player));
    }
};

// ── JTL_144 No Disintegrations (event continuation) — deal (remaining HP − 1) to the chosen unit. ────
$customDQHandlers["JTL_144#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $amount = max(0, (ObjectCurrentHP($o) - intval($o->Damage)) - 1);
    if ($amount > 0) SWUDealDamageToUnit($lastDecision, $amount, intval($player));
};

// ── JTL_253 Coordinated Front (event continuation) — the SPACE half: you may give a space unit +2/+2. ─
$customDQHandlers["JTL_253#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $space = array_merge(ZoneSearch("mySpaceArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if (empty($space)) return;
    SWUQueueMayChooseTarget(intval($player), $space,
        "You_may_give_a_space_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_253");
};

// ── JTL_160 Supporting Eta-2 — On Attack: You may give a GROUND unit +2/+0 for this phase. ───────────
$onAttackAbilities["JTL_160:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("theirGroundArena", AnyUnitFilter));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_a_ground_unit_+2/+0", "Give_+2/+0_this_phase", "APPLY_PHASE_BUFF|2|0|JTL_160");
};

// ── JTL_194 Heartless Tactics (event continuation) — exhaust + -2/-0 the chosen unit; then if it has 0
// power and isn't a leader, you may return it to its owner's hand. ───────────────────────────────────
$customDQHandlers["JTL_194#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    OnExhaustCard(intval($player), $lastDecision);
    SWUApplyPhaseDebuff($lastDecision, 2, 0, 'JTL_194');
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    if (strpos(EffectiveCardType($o) ?? '', 'Leader') !== false) return; // not a leader
    if (ObjectCurrentPower($o) !== 0) return;                            // must have 0 power
    SWUQueueMayChooseTarget(intval($player), [$lastDecision],
        "You_may_return_it_to_its_owner's_hand", "Return_to_hand", "BOUNCE_UNIT");
};

// ── JTL_088 Captain Phasma (unit) — When Played/On Attack: may give ANOTHER First Order unit +2/+2. ──
$whenPlayedAbilities["JTL_088:0"] = $onAttackAbilities["JTL_088:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed) || intval($o->UniqueID ?? 0) === $selfUid) continue;
        if (HasTrait($o->CardID, 'First Order')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_another_First_Order_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_088");
};

// ── JTL_042 Power from Pain (event continuation) — buff the chosen unit +N/+0 (N = damage on it). ─────
$customDQHandlers["JTL_042#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $n = intval($o->Damage ?? 0);
    if ($n <= 0) return; // no damage → +0/+0
    SWUApplyPhaseBuff($lastDecision, $n, 0, 'JTL_042');
};

// ── JTL_060 Desperate Commando — When Defeated: You may give a unit -1/-1 for this phase. ─────────────
$whenDefeatedAbilities["JTL_060:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_a_unit_-1/-1_this_phase", "Give_-1/-1", "APPLY_PHASE_DEBUFF|1|1|JTL_060");
};

// ── JTL_199 Blade Squadron B-Wing — When Played: If another player controls 3+ exhausted units, give a
// Shield token to a unit. ────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_199:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = GetOpponent(intval($player));
    $cnt = 0;
    foreach (GetField($opp) as $u) {
        if ($u !== null && empty($u->removed) && intval($u->Status) === 0) $cnt++; // Status 0 = exhausted
    }
    if ($cnt < 3) return;
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_Shield_token_to_a_unit", "GIVE_SHIELD");
};

// ── JTL_200 Shuttle Tydirium — On Attack: Discard a card from your deck. If it has an odd cost, you may
// give an Experience token to another unit. ──────────────────────────────────────────────────────────
$onAttackAbilities["JTL_200:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;
    if (intval(CardCost($milled)) % 2 === 0) return; // even cost → no Experience
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUid) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_an_Experience_to_another_unit", "Give_an_Experience_token", "GIVE_EXPERIENCE|1");
};

// ── JTL_122 All Wings Report In (event continuation) — exhaust each chosen space unit; create an
// X-Wing per unit exhausted. ─────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_122#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $picks = array_slice(array_filter(explode("&", $lastDecision), fn($m) => $m !== '' && $m !== '-'), 0, 2);
    $count = 0;
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed) || intval($o->Status) !== 1) continue;
        OnExhaustCard(intval($player), $mz);
        $count++;
    }
    for ($i = 0; $i < $count; $i++) SWUCreateUnitToken(intval($player), 'JTL_T02');
};

// ── JTL_063 Landing Shuttle — When Defeated: You may draw a card. ─────────────────────────────────────
$whenDefeatedAbilities["JTL_063:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Draw_a_card?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_063#0', 1);
};
$customDQHandlers["JTL_063#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    DoDrawCard(intval($player), 1);
};

// ── JTL_089 The Invisible Hand — "When Played / When this unit completes an attack (and survives):
// search the top 8 cards of your deck for a Droid unit, reveal it, and draw it. If it costs 2 or less,
// you may play it for free." Uses a JTL_089-specific finalize so the drawn Droid can route into the
// optional free-play rider. The complete-attack copy reuses the identical closure (the "(and survives)"
// gate is CollectAfterAttackTriggers' surviving-attacker null-check). ───────────────────────────────
$whenPlayedAbilities["JTL_089:0"] =
$onAttackEndAbilities["JTL_089:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    _topDeckSearchBegin(intval($player), 8,
        fn($c) => stripos(CardType($c) ?? '', 'Unit') !== false && HasTrait($c, 'Droid'),
        "count:1", "JTL_089#0");
};
// Draw the chosen Droid to hand (like TOPDECKSEARCH_FINALIZE), put the rest on the deck bottom, then —
// if the drawn Droid costs 2 or less — offer to play it for free.
$customDQHandlers["JTL_089#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $freeMz   = null;  // hand mzID of a drawn Droid eligible for the free-play rider
    foreach ($resolved['drawn'] as $cardID) {
        $handObj = AddHand(intval($player), CardID: $cardID);
        if ($handObj !== null && intval(CardCost($cardID)) <= 2) {
            $freeMz = 'myHand-' . intval($handObj->mzIndex);  // count:1 → at most one such card
        }
    }
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if ($freeMz !== null) {
        DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Play_the_drawn_Droid_for_free?");
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_089#1|' . $freeMz, 1);
    }
};
// Play the just-drawn Droid from hand for free (WhenPlayed triggers fire). Mirror SWUPlayTopDeckCard's
// turn-state guard so the nested ActivateCard doesn't double-advance JTL_089's own play.
$customDQHandlers["JTL_089#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID, $gTurnPlayer;
    $playerID = intval($player);
    $handMz = $parts[0] ?? '';
    $o = ($handMz !== '') ? GetZoneObject($handMz) : null;
    if ($o === null || !empty($o->removed)) return;
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $handMz, true);  // free play from hand
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// ── JTL_119 Resupply Carrier — When Played: You may put the top card of your deck into play as a
// resource. ──────────────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_119:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $hasCard = false;
    foreach (GetDeck(intval($player)) as $c) { if (empty($c->removed)) { $hasCard = true; break; } }
    if (!$hasCard) return;
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Put_top_of_deck_into_play_as_a_resource?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_119#0', 1);
};
$customDQHandlers["JTL_119#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $deck = GetDeck(intval($player));
    $topIdx = null;
    foreach ($deck as $i => $c) { if (empty($c->removed)) { $topIdx = $i; break; } }
    if ($topIdx === null) return;
    SWURampResourceReady(intval($player), "myDeck-" . $topIdx);
};

// ── JTL_164 Cham Syndulla — When Played: If an opponent controls more resources than you, you may put
// the top card of your deck into play as a resource. (YES branch reuses the JTL_119 ramp.) ─────────────
$whenPlayedAbilities["JTL_164:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $me  = intval($player);
    $opp = OtherPlayer($me);
    if (SWUResourceCount($opp) <= SWUResourceCount($me)) return; // opp must control MORE
    $hasCard = false;
    foreach (GetDeck($me) as $c) { if (empty($c->removed)) { $hasCard = true; break; } }
    if (!$hasCard) return;
    DecisionQueueController::AddDecision($me, 'YESNO', '-', 1, tooltip: "Put_top_of_deck_into_play_as_a_resource?");
    DecisionQueueController::AddDecision($me, 'CUSTOM', 'JTL_119#0', 1);
};

// ── JTL_154 Profundity — When Played/When Defeated: Choose a player. They discard a card from their
// hand. Then, if they have more cards in their hand than you, they discard a card from their hand. ─────
$jtl154_choose = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "You&Opponent", 1, "Choose_a_player_to_discard");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "JTL_154#0|" . intval($player), 1);
};
$whenPlayedAbilities["JTL_154:0"]   = $jtl154_choose;
$whenDefeatedAbilities["JTL_154:0"] = $jtl154_choose;
$customDQHandlers["JTL_154#0"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0]);
    $target = ($lastDecision === 'You') ? $caster : OtherPlayer($caster);
    global $playerID;
    $playerID = $caster;
    SWUDiscardCards(OtherPlayer($target), 1);  // SWUDiscardCards($p) makes OtherPlayer($p) discard → $target
    DecisionQueueController::AddDecision($caster, "CUSTOM", "JTL_154#1|{$target}|{$caster}", 1);
};
$customDQHandlers["JTL_154#1"] = function($player, $parts, $lastDecision) {
    $target = intval($parts[0]); $caster = intval($parts[1]);
    global $playerID;
    $playerID = $caster;
    $count = function($p) { $n = 0; foreach (GetHand($p) as $c) { if (empty($c->removed)) $n++; } return $n; };
    if ($count($target) > $count($caster)) SWUDiscardCards(OtherPlayer($target), 1);
};

// ── Reactive draw hook: "When an opponent draws 1+ cards during the action phase, you may give an
// Experience token to a unit." (JTL_111 Seasoned Fleet Admiral / SHD_184). For each such unit the
// drawing player's OPPONENT controls, offer one may-give. ─────────────────────────────────────────────
function _SWUOnPlayerDrew(int $drawingPlayer, int $count): void {
    if ($count < 1) return;
    if (GetCurrentPhase() !== 'MAIN') return;          // action phase only
    global $playerID;
    $savedPID = $playerID;
    $reactor  = OtherPlayer($drawingPlayer);            // the would-be JTL_111 controller
    $playerID = $reactor;
    foreach (GetUnitsInPlay($reactor) as $u) {
        if (!empty($u->removed)) continue;
        if (($u->CardID ?? '') !== 'JTL_111' && ($u->CardID ?? '') !== 'SHD_184') continue;
        $targets = array_values(array_merge(
            ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
            ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
        ));
        if (!empty($targets)) {
            SWUQueueMayChooseTarget($reactor, $targets,
                "Give_an_Experience_token_to_a_unit", "Choose_a_unit", "GIVE_EXPERIENCE|1");
        }
    }
    $playerID = $savedPID;
}

// ── JTL_158 Crackshot V-Wing — When Played: If you control no other Fighter units, deal 1 to this unit.
$whenPlayedAbilities["JTL_158:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = $self ? intval($self->UniqueID ?? 0) : 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $selfUid) continue;
        if (HasTrait($u->CardID ?? '', 'Fighter')) return;   // controls another Fighter → no self-damage
    }
    SWUDealDamageToUnit($mzID, 1, intval($player));
};

// ── JTL_201 Ahsoka Tano — When Played: An opponent discards a card from their hand. If it's a unit, you
// may exhaust a unit. ──────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_201:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $hasCard = false;
    foreach (GetHand($opp) as $c) { if (empty($c->removed)) { $hasCard = true; break; } }
    if (!$hasCard) return;
    SWUDiscardCards(intval($player), 1);   // the opponent discards a card of their choice
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_201#0|" . intval($player), 1);
};
$customDQHandlers["JTL_201#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0]);
    $playerID = $caster;
    $opp = OtherPlayer($caster);
    $disc = GetDiscard($opp);
    $last = null;
    for ($i = count($disc) - 1; $i >= 0; $i--) { if (empty($disc[$i]->removed)) { $last = $disc[$i]; break; } }
    if ($last === null) return;
    if (stripos(CardType($last->CardID ?? '') ?? '', 'unit') === false) return;   // discarded card wasn't a unit
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget($caster, $units, "Exhaust_a_unit", "Choose_a_unit_to_exhaust", "EXHAUST_UNIT");
};

// ── JTL_047 Admiral Yularen — When Played: choose Grit / Restore 1 / Sentinel / Shielded; while in play,
// each friendly Vehicle gains the chosen keyword (stored per-UID, read by the conditional-keyword auras).
$whenPlayedAbilities["JTL_047:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    $uid = $obj ? intval($obj->UniqueID ?? 0) : 0;
    if ($uid === 0) return;
    DecisionQueueController::AddDecision($player, 'OPTIONCHOOSE', 'Grit&Restore_1&Sentinel&Shielded', 1, "Choose_a_keyword_for_your_Vehicles");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_047#0|{$uid}", 1);
};
$customDQHandlers["JTL_047#0"] = function($player, $parts, $lastDecision) {
    $uid = intval($parts[0] ?? 0);
    $map = ['Grit' => 'GRIT', 'Restore_1' => 'RESTORE', 'Sentinel' => 'SENTINEL', 'Shielded' => 'SHIELDED'];
    $kw  = $map[$lastDecision] ?? null;
    if ($uid === 0 || $kw === null) return;
    AddGlobalEffects(intval($player), "SWU_YULAREN_{$uid}_{$kw}");
};

// ── JTL_192 In Debt to Crimson Dawn — regroup ready tax: pay 2 (YES) to keep ready, else exhaust. ─────
$customDQHandlers["JTL_192#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $mz  = $parts[0] ?? '';
    $obj = GetZoneObject($mz);
    if ($obj === null || !empty($obj->removed)) return;
    if ($lastDecision === 'YES' && SWUResourceCount(intval($player), true) >= 2) {
        SWUPayCost(intval($player), 2, 0);   // pay 2 ready resources to keep it ready
    } else {
        $obj->Status = 0;                    // exhaust it
    }
};

// ── JTL_227 Superheavy Ion Cannon — granted On Attack: may exhaust an enemy non-leader unit; if you do,
// deal indirect damage equal to its power to that player. ─────────────────────────────────────────────
$onAttackAbilities["JTL_227:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_values(array_merge(
        ZoneSearch('theirGroundArena', NonLeaderUnitFilter),
        ZoneSearch('theirSpaceArena',  NonLeaderUnitFilter)
    ));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Exhaust_an_enemy_unit_(deal_indirect_equal_to_its_power)", "Choose_an_enemy_non-leader_unit", "JTL_227#0");
};
$customDQHandlers["JTL_227#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $pow = max(0, intval(ObjectCurrentPower($obj)));
    $obj->Status = 0;   // exhaust the enemy unit
    if ($pow > 0) SWUDealIndirectDamage(intval($player), $pow, OtherPlayer(intval($player)));
};

// ── JTL_087 TIE Ambush Squadron — When Played/When Defeated: Create a TIE Fighter token. ──────────────
$jtl087_tie = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'JTL_T01'); // TIE Fighter (Space, 1/1)
};
$whenPlayedAbilities["JTL_087:0"]   = $jtl087_tie;
$whenDefeatedAbilities["JTL_087:0"] = $jtl087_tie;

// ── JTL_090 Executor — When Played/On Attack/When Defeated: Create 3 TIE Fighter tokens. ──────────────
$jtl090_3tie = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    for ($i = 0; $i < 3; $i++) SWUCreateUnitToken(intval($player), 'JTL_T01');
};
$whenPlayedAbilities["JTL_090:0"]   = $jtl090_3tie;
$onAttackAbilities["JTL_090:0"]     = $jtl090_3tie;
$whenDefeatedAbilities["JTL_090:0"] = $jtl090_3tie;

// ── JTL_097 Leia Organa — When Played: attack with a Pilot unit. It gets +1/+0 and gains Restore 1 for
// this attack. (Her own Restore 1 keyword is auto-wired.) ──────────────────────────────────────────────
$whenPlayedAbilities["JTL_097:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $pilots = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
            $isPilot = HasTrait($u->CardID, 'Pilot');
            if (!$isPilot && !empty($u->Subcards) && is_array($u->Subcards)) {
                foreach ($u->Subcards as $sub) {       // "or a unit with a Pilot on it"
                    $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
                    $cap  = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
                    $pil  = is_array($sub) ? !empty($sub['IsPilot'])   : !empty($sub->IsPilot);
                    if (!$cap && ($pil || ($scid !== '' && HasTrait($scid, 'Pilot')))) { $isPilot = true; break; }
                }
            }
            if ($isPilot) $pilots[] = "{$zone}-{$i}";
        }
    }
    if (empty($pilots)) return;
    SWUQueueMayChooseTarget(intval($player), $pilots,
        "Attack_with_a_Pilot_unit_(+1/+0,_Restore_1)", "Choose_a_Pilot_unit_to_attack_with", "JTL_097#0");
};
$customDQHandlers["JTL_097#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    SWUAddAttackPowerBonus($lastDecision, 1);                                   // +1/+0 for this attack
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('RESTORE', [1], SWU_DUR_ATTACK)); // Restore 1 this attack
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_123 Dogfight — attack with the chosen unit (even if exhausted); it can't attack bases. ────────
$customDQHandlers["JTL_123#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    BeginSWUAttack(intval($player), $lastDecision, true);   // noBases = true (can't attack bases this attack)
};

// ── JTL_124 Tandem Assault — the chosen space unit attacks, then a ground unit (+2/+0) attacks. ────────
$customDQHandlers["JTL_124#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SetSWUVar('SWU_CHAINED_ATTACK', "0,0,2,{$uid},ground"); // not-rebel, mandatory, +2, exclude self, ground only
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_202 Black Squadron Scout Wing — on YES, the host gets +1/+0 and attacks. ──────────────────────
$customDQHandlers["JTL_202#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    $obj = GetZoneObject($mz);
    if ($obj === null || !empty($obj->removed) || intval($obj->Status) !== 1) return;
    SWUAddAttackPowerBonus($mz, 1);
    BeginSWUAttack(intval($player), $mz);
};

// ── JTL_228 Barrel Roll — chosen space unit attacks; after, may exhaust a space unit. ─────────────────
$customDQHandlers["JTL_228#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    BeginSWUAttack(intval($player), $lastDecision);
    // After completing the attack: may exhaust a space unit (EXHAUST_UNIT validates the chosen target).
    $spaceUnits = array_values(array_merge(
        ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (!empty($spaceUnits)) {
        SWUQueueMayChooseTarget(intval($player), $spaceUnits,
            "Exhaust_a_space_unit", "Choose_a_space_unit_to_exhaust", "EXHAUST_UNIT");
    }
};

// ── JTL_231 Punch It — chosen Vehicle gets +2/+0, then attacks. ───────────────────────────────────────
$customDQHandlers["JTL_231#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_261 Attack Run — chosen space unit attacks, then a chained second space unit attacks. ──────────
$customDQHandlers["JTL_261#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SetSWUVar('SWU_CHAINED_ATTACK', "0,0,0,{$uid},space");  // not-rebel, mandatory, +0, exclude self, space only
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_156 Trench Run — chosen Fighter gets +4/+0 + a granted On-Attack (JTL_156 marker), then attacks.
$customDQHandlers["JTL_156#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    SWUAddAttackPowerBonus($lastDecision, 4);
    AddTurnEffect($lastDecision, 'JTL_156');   // granted On-Attack this attack (registry duration = attack)
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_177 Stay on Target — chosen Vehicle gets +2/+0 + a granted base-damage→draw (JTL_177), attacks.
$customDQHandlers["JTL_177#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    AddTurnEffect($lastDecision, 'JTL_177');
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_193 I Have You Now — chosen Vehicle attacks; all damage to it is prevented this attack (JTL_193).
$customDQHandlers["JTL_193#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    AddTurnEffect($lastDecision, 'JTL_193');
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── JTL_235 Commandeer — take control of the chosen Vehicle, ready it, mark it for return next regroup.
$customDQHandlers["JTL_235#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUTakeControlOfUnit(intval($player), $lastDecision);   // moves into the caster's arena
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    $u = GetZoneObject($mz);
    if ($u !== null && empty($u->removed)) {
        OnReadyCard(intval($player), $mz);                                 // ready it
        AddGlobalEffects(intval($player), 'SWU_JTL235_RETURN_' . $uid);    // return at next regroup
    }
};

// ── JTL_244 There Is No Escape — the chosen units lose all abilities (this round). ────────────────────
$customDQHandlers["JTL_244#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'JTL_244');
    }
};

// ── JTL_219 Rafa Martez — When Played/On Attack: Deal 1 damage to a friendly unit and ready a resource.
$jtl219 = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $friendly = array_values(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)
    ));
    if (empty($friendly)) { _SWUJTL219ReadyResource(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $friendly, "Deal_1_to_a_friendly_unit_(then_ready_a_resource)", "JTL_219#0");
};
$whenPlayedAbilities["JTL_219:0"] = $jtl219;
$onAttackAbilities["JTL_219:0"]   = $jtl219;
$customDQHandlers["JTL_219#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision !== null && $lastDecision !== '-' && $lastDecision !== '' && $lastDecision !== 'PASS') {
        $obj = GetZoneObject($lastDecision);
        if ($obj !== null && empty($obj->removed)) SWUDealDamageToUnit($lastDecision, 1, intval($player));
    }
    _SWUJTL219ReadyResource(intval($player));
};
function _SWUJTL219ReadyResource(int $player): void {
    $res = &GetResources($player);
    foreach ($res as &$r) {
        if (empty($r->removed) && intval($r->Status) !== 1) { $r->Status = 1; break; }
    }
    unset($r);
}

// ── JTL_139 Dengar (pilot) — granted "On Attack: Deal 2 indirect to a player (3 if the attached unit is
// an Underworld unit)." ───────────────────────────────────────────────────────────────────────────────
$onAttackAbilities["JTL_139:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $amt = ($host !== null && HasTrait($host->CardID ?? '', 'Underworld')) ? 3 : 2;
    SWUDealIndirectToChosenPlayer(intval($player), $amt);
};

// ── JTL_226 Radiant VII — When Played: 5 indirect to a player (its -1/-0-per-damage aura is a passive). ─
$whenPlayedAbilities["JTL_226:0"] = function($player, $mzID) { SWUDealIndirectToChosenPlayer(intval($player), 5); };

// ── JTL_149 Red Squadron Y-Wing — On Attack: 3 indirect to the defending player. ──────────────────────
$onAttackAbilities["JTL_149:0"] = function($player, $mzID) {
    SWUDealIndirectDamage(intval($player), 3, OtherPlayer(intval($player)));
};

// ── JTL_162 Droid Missile Platform — When Defeated: 3 indirect to a player. ───────────────────────────
$whenDefeatedAbilities["JTL_162:0"] = function($player, $mzID) { SWUDealIndirectToChosenPlayer(intval($player), 3); };

// ── JTL_183 Zygerrian Starhopper — When Defeated: 2 indirect to a player. ─────────────────────────────
$whenDefeatedAbilities["JTL_183:0"] = function($player, $mzID) { SWUDealIndirectToChosenPlayer(intval($player), 2); };

// ── JTL_116 Dornean Gunship — When Played: deal indirect damage to a player equal to the number of
// Vehicle units you control. ──────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_116:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $cnt = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (HasTrait($u->CardID ?? '', 'Vehicle')) $cnt++; }
    if ($cnt > 0) SWUDealIndirectToChosenPlayer(intval($player), $cnt);
};

// ── JTL_132 First Order Stormtrooper — On Attack/When Defeated: 1 indirect damage to a player. ─────────
$jtl132_indirect = function($player, $mzID) { SWUDealIndirectToChosenPlayer(intval($player), 1); };
$onAttackAbilities["JTL_132:0"]     = $jtl132_indirect;
$whenDefeatedAbilities["JTL_132:0"] = $jtl132_indirect;

// ── JTL_237 TIE Bomber — On Attack: 3 indirect damage to the defending player (the opponent). ─────────
$onAttackAbilities["JTL_237:0"] = function($player, $mzID) {
    SWUDealIndirectDamage(intval($player), 3, OtherPlayer(intval($player)));
};

// ── JTL_133 Allegiant General Pryde — On Attack: if you have the initiative, deal 2 indirect to a player.
// (The passive "when indirect is dealt to a unit → may defeat a non-unique upgrade on it" reaction lives
// in SWUApplyIndirectAssignment.)
$onAttackAbilities["JTL_133:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (PlayerHasIniative(intval($player))) SWUDealIndirectToChosenPlayer(intval($player), 2);
};

// ── JTL_152 Tactical Heavy Bomber — On Attack: indirect = power to the defending player; if a base is
// damaged this way, draw. (Reactive seam via the indirect "then" continuation.)
$onAttackAbilities["JTL_152:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($mzID);
    $power = ($o !== null) ? ObjectCurrentPower($o) : 0;
    if ($power <= 0) return;
    SWUDealIndirectDamage(intval($player), $power, OtherPlayer(intval($player)), "JTL_152#0");
};
$customDQHandlers["JTL_152#0"] = function($player, $parts, $lastDecision) {
    global $gLastIndirectBaseDmg;
    if (intval($gLastIndirectBaseDmg) > 0) DoDrawCard(intval($player), 1);
};

// ── JTL_218 Guerilla Soldier — When Played: 3 indirect to a player; if a base is damaged this way, ready
// this unit (carry the source UID through the continuation).
$whenPlayedAbilities["JTL_218:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($mzID);
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    SWUDealIndirectToChosenPlayer(intval($player), 3, "JTL_218#0~{$uid}");
};
$customDQHandlers["JTL_218#0"] = function($player, $parts, $lastDecision) {
    global $gLastIndirectBaseDmg, $playerID;
    if (intval($gLastIndirectBaseDmg) <= 0) return;
    $uid = intval($parts[0] ?? 0);
    $playerID = intval($player);
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};

// ── JTL_222 Kimogila Heavy Fighter — When Played: 3 indirect to a player; exhaust each unit damaged this
// way (from the continuation's damaged-UID list).
$whenPlayedAbilities["JTL_222:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealIndirectToChosenPlayer(intval($player), 3, "JTL_222#0");
};
// JTL_009 Boba Fett — resolve the "exhaust this leader → 1 indirect" non-combat-damage reaction.
$customDQHandlers["JTL_009#0"] = function($player, $parts, $lastDecision) {
    $dealer = intval($parts[0] ?? $player);
    RemoveGlobalEffect($dealer, 'SWU_BOBA_009_PENDING');
    if ($lastDecision !== "YES") return;
    if (!_SWULeaderReadyUndeployed($dealer, 'JTL_009')) return;
    _SWUExhaustUndeployedLeader($dealer, 'JTL_009');
    SWUDealIndirectToChosenPlayer($dealer, 1);
};

$customDQHandlers["JTL_222#0"] = function($player, $parts, $lastDecision) {
    global $gLastIndirectUnitUIDs, $playerID;
    if (!is_array($gLastIndirectUnitUIDs)) return;
    $playerID = intval($player);
    foreach ($gLastIndirectUnitUIDs as $uid) {
        $mz = SWUFindMzByUID(intval($uid));
        if ($mz !== null) OnExhaustCard(intval($player), $mz);
    }
};

// ── JTL_240 Fett's Firespray — When Played/On Attack: 1 indirect to a player (2 if you control Boba Fett).
$jtl240_indirect = function($player, $mzID) {
    $amt = _SWUControlsTitle(intval($player), ['Boba Fett']) ? 2 : 1;
    SWUDealIndirectToChosenPlayer(intval($player), $amt);
};
$whenPlayedAbilities["JTL_240:0"] = $jtl240_indirect;
$onAttackAbilities["JTL_240:0"]   = $jtl240_indirect;

// ── JTL_129 Focus Fire — each friendly Vehicle in the chosen unit's arena deals its power to it. ──────
$customDQHandlers["JTL_129#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $arena = $obj->Location ?? 'GroundArena';   // 'GroundArena' or 'SpaceArena'
    $sum = 0;
    foreach (ZoneSearch('my' . $arena, AnyUnitFilter) as $mz) {
        $u = GetZoneObject($mz);
        if ($u !== null && empty($u->removed) && HasTrait($u->CardID ?? '', 'Vehicle')) $sum += intval(ObjectCurrentPower($u));
    }
    if ($sum > 0) SWUDealDamageToUnit($lastDecision, $sum, intval($player));
};

// ── JTL_174 Hotshot Maneuver — friendly unit chosen; count its On Attack abilities (the same set
// CollectCombatStep1Triggers fires: printed windows + upgrade-granted), deal 2 to that many DIFFERENT
// enemy units, then attack with the chosen unit. ──────────────────────────────────────────────────────
$customDQHandlers["JTL_174#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID, $onAttackAbilities;
    $playerID = intval($player);
    $u = GetZoneObject($lastDecision);
    if ($u === null || !empty($u->removed)) return;
    $uid = intval($u->UniqueID ?? 0);
    // Count On Attack abilities: printed windows for this CardID + upgrade-granted on-attack.
    $n = 0;
    foreach (array_keys($onAttackAbilities) as $k) {
        if (preg_match('/^' . preg_quote($u->CardID, '/') . ':\d+$/', $k)) $n++;
    }
    foreach (GetUpgradesOnUnit($u) as $up) {
        if (isset($onAttackAbilities[($up->CardID ?? '') . ':0'])) $n++;
    }
    $enemies = array_values(array_merge(
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)));
    $k = min($n, count($enemies));
    if ($k <= 0) {
        // No On Attack abilities (or no enemy units) → skip damage, just attack.
        $attMz = SWUFindMzByUID($uid);
        if ($attMz !== null) BeginSWUAttack(intval($player), $attMz);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "{$k}|{$k}|" . implode("&", $enemies), 1,
        tooltip: "Deal_2_to_{$k}_different_enemy_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_174#1|{$uid}", 1);
};
$customDQHandlers["JTL_174#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    // Snapshot chosen enemy UIDs, deal 2 to each (index-shift safe).
    $targetUids = [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        foreach (explode('&', $lastDecision) as $mz) {
            $mz = trim($mz);
            if ($mz === '' || $mz === '-') continue;
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targetUids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($targetUids as $tuid) {
        $mz = SWUFindMzByUID($tuid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    }
    // Then attack with the chosen unit.
    $attMz = SWUFindMzByUID($uid);
    if ($attMz !== null) BeginSWUAttack(intval($player), $attMz);
};

// ── JTL_127 Lightspeed Assault — friendly space unit chosen (to defeat); capture its power, then choose
// the enemy space unit to receive that damage. ───────────────────────────────────────────────────────
$customDQHandlers["JTL_127#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $f = GetZoneObject($lastDecision);
    if ($f === null || !empty($f->removed)) return;
    $fuid = intval($f->UniqueID ?? 0);
    $fpow = intval(ObjectCurrentPower($f));
    $enemy = ZoneSearch('theirSpaceArena', AnyUnitFilter);
    if (empty($enemy)) return; // no enemy space unit → don't defeat the friendly (fizzle)
    SWUQueueChooseTarget(intval($player), $enemy,
        "Deal_{$fpow}_damage_to_an_enemy_space_unit", "JTL_127#1|{$fuid}|{$fpow}");
};
// Defeat the friendly, deal its power to the chosen enemy, then "if you do" deal indirect = the enemy
// unit's power to its controller.
$customDQHandlers["JTL_127#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $fuid = intval($parts[0] ?? 0);
    $fpow = intval($parts[1] ?? 0);
    $e = GetZoneObject($lastDecision);
    if ($e === null || !empty($e->removed)) return;
    $euid        = intval($e->UniqueID ?? 0);
    $epow        = intval(ObjectCurrentPower($e));           // captured before any defeat (power ≠ HP)
    $econtroller = intval($e->Controller ?? GetOpponent(intval($player)));
    $fmz = SWUFindMzByUID($fuid);
    if ($fmz === null) return;                               // friendly already gone → can't complete
    SWUDefeatUnit(intval($player), $fmz);                    // defeat the friendly space unit
    $emz = SWUFindMzByUID($euid);                            // re-resolve enemy after the cleanup
    if ($emz !== null && $fpow > 0) SWUDealDamageToUnit($emz, $fpow, intval($player));
    if ($epow > 0) SWUDealIndirectDamage(intval($player), $epow, $econtroller);
};

// ── JTL_131 Turbolaser Salvo — arena chosen; pick the friendly space dealer (its power is the AOE). ───
$customDQHandlers["JTL_131#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    $dealers = ZoneSearch('mySpaceArena', AnyUnitFilter);
    if (empty($dealers)) return;
    SWUQueueChooseTarget(intval($player), $dealers,
        "A_friendly_space_unit_deals_its_power_to_each_enemy_in_that_arena", "JTL_131#1|{$arena}");
};
// Dealer chosen → deal its power to each enemy unit in the chosen arena (snapshot UIDs, index-shift safe).
$customDQHandlers["JTL_131#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $arena = (($parts[0] ?? 'GroundArena') === 'SpaceArena') ? 'SpaceArena' : 'GroundArena';
    $dealer = GetZoneObject($lastDecision);
    if ($dealer === null || !empty($dealer->removed)) return;
    $pow = intval(ObjectCurrentPower($dealer));
    if ($pow <= 0) return;
    $uids = [];
    foreach (ZoneSearch('their' . $arena, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, $pow, intval($player));
    }
};

// ── JTL_180 Piercing Shot — defeat all Shield tokens (SOR_T02) on the chosen unit, then deal 3 to it. ──
$customDQHandlers["JTL_180#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    if (is_array($obj->Subcards ?? null)) {
        foreach ($obj->Subcards as &$sub) {
            $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            if ($scid === 'SOR_T02') { if (is_array($sub)) $sub['removed'] = true; else $sub->removed = true; }
        }
        unset($sub);
    }
    DecisionQueueController::CleanupRemovedCards();
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
};

// ── JTL_043 No Glory, Only Results — take control of the chosen non-leader unit, then defeat it. ──────
$customDQHandlers["JTL_043#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $newMz = SWUTakeControlOfUnit(intval($player), $lastDecision);   // unit moves into the caster's arena
    if ($newMz === '') return;                                       // take control blocked (LAW_149 Rey) — nothing to defeat
    SWUDefeatUnit(intval($player), $newMz);                          // then defeat it: now friendly, so it lands in its owner's discard
};

// ── JTL_205 Daring Raid — put the chosen discarded card on the bottom of its owner's deck, then create
// an X-Wing token (only fires when a card was actually put — declines no-op above). ────────────────────
$customDQHandlers["JTL_205#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $owner = (strpos($lastDecision, 'my') === 0) ? intval($player) : GetOpponent(intval($player));
    $cid = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom($owner, [$cid]);
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};

// ── JTL_121 Salvage — play the chosen Vehicle unit from the discard at cost, then deal 1 damage to it.
$customDQHandlers["JTL_121#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $idx = intval(substr($lastDecision, strrpos($lastDecision, '-') + 1));
    $newMz = SWUPlayDiscardUnitDiscounted(intval($player), $idx, 0); // pay full cost (discount 0)
    if ($newMz === '') return;
    SWUDealDamageToUnit($newMz, 1, intval($player));
};

// ── JTL_074 Close the Shield Gate — arm the one-shot base-damage prevention on the chosen base's owner.
$customDQHandlers["JTL_074#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $owner = (strpos($lastDecision, 'their') === 0) ? GetOpponent(intval($player)) : intval($player);
    AddGlobalEffects($owner, 'SWU_SHIELD_GATE');
};

// ── JTL_155 They Hate That Ship — play the chosen Vehicle from hand at a 3-resource discount. The event
// owns the action (FINISH_PLAY_CARD), so neutralise the nested ActivateCard's after-action like SOR_219.
$customDQHandlers["JTL_155#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID, $gTurnPlayer;
    $playerID  = intval($player);
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, 3);
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// ── JTL_220 Skyway Cloud Car — When Defeated: may return a non-leader unit with 2 or less power to its
// owner's hand. ──────────────────────────────────────────────────────────────────────────────────────
$whenDefeatedAbilities["JTL_220:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && ObjectCurrentPower($o) <= 2) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_return_a_non-leader_unit_with_2_or_less_power", "Return_to_hand", "BOUNCE_UNIT");
};

// ── JTL_232 Jump to Lightspeed (event continuation) — return the chosen space unit AND its non-leader
// upgrades to owners' hands. (The "play a copy for free this phase" rider is deferred.) ───────────────
$customDQHandlers["JTL_232#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    // Return non-leader, non-token upgrades to their owners' hands and drop them from the unit so the
    // subsequent bounce doesn't also discard them.
    if (!empty($o->Subcards) && is_array($o->Subcards)) {
        $keep = [];
        foreach ($o->Subcards as $sub) {
            $scid  = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            $sown  = is_array($sub) ? intval($sub['Owner'] ?? $player) : intval($sub->Owner ?? $player);
            $isCap = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
            $type  = strtolower(CardType($scid) ?? '');
            if ($scid !== '' && !$isCap && strpos($type, 'leader') === false && strpos($type, 'token') === false) {
                AddHand($sown <= 0 ? $player : $sown, CardID: $scid);
            } else {
                $keep[] = $sub;
            }
        }
        $o->Subcards = $keep;
    }
    SWUBounceUnit(intval($player), $lastDecision); // returns the unit to its owner's hand
};

// ── JTL_233 Sweep the Area (event continuation) — return up to 2 non-leader units in the SAME arena with
// combined printed cost <= 3 to their owners' hands. Validates the picks (the harness doesn't). ───────
$customDQHandlers["JTL_233#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $picks = [];
    foreach (explode("&", $lastDecision) as $mz) {
        $mz = trim($mz);
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
        $arena = (strpos($mz, 'Space') !== false) ? 'Space' : 'Ground';
        $picks[] = ['uid' => intval($o->UniqueID ?? 0), 'arena' => $arena, 'cost' => intval(CardCost($o->CardID))];
    }
    $picks = array_slice($picks, 0, 2);
    if (empty($picks)) return;
    // Validate: all in the same arena and combined cost <= 3.
    $arena0 = $picks[0]['arena']; $total = 0;
    foreach ($picks as $p) { if ($p['arena'] !== $arena0) return; $total += $p['cost']; }
    if ($total > 3) return;
    foreach ($picks as $p) {
        $mz = SWUFindMzByUID($p['uid']);
        if ($mz !== null && $mz !== '') SWUBounceUnit(intval($player), $mz);
    }
};

// ── JTL_217 Death Space Skirmisher — When Played: If you control another space unit, you may exhaust a
// unit. ──────────────────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_217:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $another = false;
    foreach (ZoneSearch("mySpaceArena", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUid) { $another = true; break; }
    }
    if (!$another) return;
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "You_may_exhaust_a_unit", "Exhaust_a_unit", "EXHAUST_UNIT");
};

// ── JTL_195 Cat and Mouse (event continuation) — exhaust the chosen enemy; ready a friendly in the
// same arena with power <= that enemy's power. ───────────────────────────────────────────────────────
$customDQHandlers["JTL_195#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $eo = GetZoneObject($lastDecision);
    if ($eo === null || !empty($eo->removed)) return;
    $epower = ObjectCurrentPower($eo);
    OnExhaustCard(intval($player), $lastDecision);
    $arena = (strpos($lastDecision, 'Space') !== false) ? 'mySpaceArena' : 'myGroundArena';
    $friendly = [];
    foreach (ZoneSearch($arena, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && ObjectCurrentPower($o) <= $epower) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly,
        "Ready_a_friendly_unit_with_power_<=_that_enemy", "READY_UNIT");
};

// ── JTL_206 Fly Casual (event continuation) — ready the chosen Vehicle; it can't attack bases this phase.
$customDQHandlers["JTL_206#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    OnReadyCard(intval($player), $lastDecision);
    AddTurnEffect($lastDecision, 'CANT_ATTACK_BASES');
};

// ── JTL_135 Special Forces TIE Fighter — When Played: If an opponent controls more space units than
// you, ready this unit. ──────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_135:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $mine = count(ZoneSearch("mySpaceArena", AnyUnitFilter));      // includes the just-entered unit
    $opp  = count(ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if ($opp > $mine) OnReadyCard(intval($player), $mzID);
};

// ── JTL_157 Relentless Firespray — On Attack: Ready this unit. Once each round. ───────────────────────
$onAttackAbilities["JTL_157:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if ($o === null) return;
    if (!SWUHasUseAvailable($o)) return; // once per round — per-unit NumUses budget
    SWUConsumeUse($o);
    OnReadyCard(intval($player), $mzID);
};

// ── JTL_178 Face Off (event continuation) — ready the chosen enemy, then ready a friendly in the same
// arena. ─────────────────────────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_178#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    OnReadyCard(intval($player), $lastDecision); // ready the enemy
    $arena = (strpos($lastDecision, 'Space') !== false) ? 'mySpaceArena' : 'myGroundArena';
    $friendly = ZoneSearch($arena, AnyUnitFilter);
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly,
        "Ready_a_friendly_unit_in_the_same_arena", "READY_UNIT");
};

// ── JTL_243 Quasar TIE Carrier — On Attack: Create a TIE Fighter token. ──────────────────────────────
$onAttackAbilities["JTL_243:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T01'); // TIE Fighter (Space, 1/1)
};

// ── JTL_252 Tantive IV — Sentinel (auto) + When Played: Create an X-Wing token. ──────────────────────
$whenPlayedAbilities["JTL_252:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};

// ── JTL_117 General Draven — When Played/On Attack: Create an X-Wing token. ──────────────────────────
$whenPlayedAbilities["JTL_117:0"] = $onAttackAbilities["JTL_117:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};

// ── JTL_082 Kijimi Patrollers — When Played: Create a TIE Fighter token. ─────────────────────────────
$whenPlayedAbilities["JTL_082:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T01'); // TIE Fighter (Space, 1/1)
};

// ── JTL_099 Veteran Fleet Officer — When Played: Create an X-Wing token. ─────────────────────────────
$whenPlayedAbilities["JTL_099:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};

// ── JTL_067 Cloaked StarViper — When Played: Give 2 Shield tokens to this unit. ──────────────────────
$whenPlayedAbilities["JTL_067:0"] = function($player, $mzID) {
    GiveShieldToken(intval($player), $mzID);
    GiveShieldToken(intval($player), $mzID);
};

// ── JTL_070 U-Wing Lander — When Played: Give 3 Experience tokens to this unit. ──────────────────────
$whenPlayedAbilities["JTL_070:0"] = function($player, $mzID) {
    for ($i = 0; $i < 3; $i++) DoGiveExperienceToken(intval($player), $mzID);
};
// JTL_070 — "When this unit completes an attack (and survives): You may attach an upgrade on this unit
// to another eligible friendly Vehicle unit." The "(and survives)" gate is CollectAfterAttackTriggers'
// surviving-attacker null-check. Reuses the move-upgrade subsystem scoped to this host as the source
// and friendly Vehicles as the destination. Skip entirely if no other friendly Vehicle can receive it.
$onAttackEndAbilities["JTL_070:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || !empty($self->removed)) return;
    $selfUid = intval($self->UniqueID ?? 0);
    $hasDest = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $selfUid) continue;
        if (HasTrait($u->CardID ?? '', 'Vehicle')) { $hasDest = true; break; }
    }
    if (!$hasDest) return;
    SWUQueueMoveUpgrade(intval($player), 'nonpilot',
        "Attach_an_upgrade_on_this_unit_to_another_friendly_Vehicle", $mzID, 'friendlyVehicle');
};

// ── JTL_071 CR90 Relief Runner — Restore 2 (auto) + When Defeated: Heal up to 3 from a unit or base. ──
$whenDefeatedAbilities["JTL_071:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter),
        ['myBase-0', 'theirBase-0']
    );
    SWUQueueChooseTarget(intval($player), $targets, "Heal_up_to_3_from_a_unit_or_base", "HEAL_TARGET|3");
};

// ── JTL_072 Wing Guard Security Team — Sentinel (auto) + When Played: Give a Shield to each of up to 2
// Fringe units. ──────────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_072:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Fringe')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    $max = min(2, count($targets));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $max . "|" . implode("&", $targets), 1, tooltip: "Give_a_Shield_to_up_to_2_Fringe_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_072#0", 1);
};
$customDQHandlers["JTL_072#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $picks = array_slice(array_filter(explode("&", $lastDecision), fn($m) => $m !== '' && $m !== '-'), 0, 2);
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) GiveShieldToken(intval($player), $mz);
    }
};

// ── JTL_033 Onyx Squadron Brute — When Defeated: Heal 2 damage from a base. ──────────────────────────
$whenDefeatedAbilities["JTL_033:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Heal_2_damage_from_a_base", "HEAL_TARGET|2");
};

// ── JTL_044 Echo Base Engineer — When Played: You may give a Shield token to a damaged Vehicle. ───────
$whenPlayedAbilities["JTL_044:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage) > 0 && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_a_Shield_to_a_damaged_Vehicle", "Give_a_Shield_token", "GIVE_SHIELD");
};

// ── JTL_062 Silver Angel — reactive: When 1+ damage is healed from this unit, the controller may deal
// 1 to a space unit. Fired from OnHealUnit (CombatLogic) when a unit is healed. ──────────────────────
function _SWUOnUnitHealed($obj): void {
    global $playerID;
    if ($obj === null || !empty($obj->removed)) return;
    if (($obj->CardID ?? '') !== 'JTL_062' || LostAbilities($obj)) return;
    $controller = intval($obj->Controller ?? 0);
    if ($controller <= 0) return;
    $playerID = $controller;
    $targets = array_merge(ZoneSearch("mySpaceArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget($controller, $targets,
        "You_may_deal_1_to_a_space_unit", "Deal_1_to_a_space_unit", "DEAL_UNIT_DAMAGE|1");
}

// ── JTL_250 Sabine's Masterpiece — On Attack: for EACH aspect you control among your units, run its
// effect (all applicable, in printed order): Vigilance→heal 2 from a base; Command→give an Experience
// token to a unit; Aggression→deal 1 to a unit or base; Cunning→exhaust or ready a resource.
// Each effect sits at a higher block so its sub-decision fully resolves before the next one is offered.
$onAttackAbilities["JTL_250:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $p = intval($player);
    $allUnits = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    $block = 1;
    // Vigilance → heal 2 from a base. Healing the enemy base is never beneficial, so this auto-targets
    // the player's own base (a base-only MZCHOOSE auto-resolves to nothing — MZCountChoices doesn't count
    // base zones, so it can't be presented in-combat anyway).
    if (_SWUControlsUnitWithAspect($p, 'Vigilance')) {
        OnHealBase($p, $p, 2);
    }
    // Command/Aggression use MZMAYCHOOSE (the proven in-combat OnAttack choose — a mandatory multi-target
    // MZCHOOSE auto-resolves to nothing here because OnAttackTrigger restores $playerID before
    // MZCountChoices runs). The player would never decline a beneficial effect.
    if (_SWUControlsUnitWithAspect($p, 'Command') && !empty($allUnits)) {
        SWUQueueMayChooseTarget($p, $allUnits, "Give_an_Experience_token_to_a_unit",
            "Give_an_Experience_token_to_a_unit", "GIVE_EXPERIENCE|1", $block++);
    }
    if (_SWUControlsUnitWithAspect($p, 'Aggression')) {
        $targets = array_merge($allUnits, ['myBase-0', 'theirBase-0']);
        SWUQueueMayChooseTarget($p, $targets, "Deal_1_damage_to_a_unit_or_a_base",
            "Deal_1_damage_to_a_unit_or_a_base", "DEAL_TARGET|1", $block++);
    }
    if (_SWUControlsUnitWithAspect($p, 'Cunning')) {
        DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", "Exhaust&Ready", $block, tooltip:"Exhaust_or_ready_a_resource");
        DecisionQueueController::AddDecision($p, "CUSTOM", "JTL_250#0", $block);
        $block++;
    }
};

$customDQHandlers["JTL_250#0"] = function($player, $parts, $lastDecision) {
    $p = intval($player);
    if ($lastDecision === 'Exhaust') SWUExhaustResources($p, 1);
    elseif ($lastDecision === 'Ready') SWUReadyResources($p, 1);
};

// ── JTL_039 Chimaera — When Played: You may use a "When Defeated" ability on another friendly unit. ──
// Offers any other friendly unit that has a When-Defeated ability; the chosen one's ability fires
// (the unit stays in play). Reuses the SWUUseWhenDefeatedAbility primitive (Phase 23).
$whenPlayedAbilities["JTL_039:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = $mzID;
    $targets = [];
    foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
        if ($mz === $self) continue;
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (!HasWhenDefeatedAbility($o->CardID)) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Use_a_When_Defeated_ability_on_another_friendly_unit",
        "Use_a_When_Defeated_ability_on_another_friendly_unit", "JTL_039#0");
};

$customDQHandlers["JTL_039#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    SWUUseWhenDefeatedAbility(intval($player), $obj->CardID, $lastDecision);
};
// JTL_039 — When Defeated: Create 2 TIE Fighter tokens.
$whenDefeatedAbilities["JTL_039:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T01');
    SWUCreateUnitToken(intval($player), 'JTL_T01');
};

// ── JTL_126 Eject continuation — detach the chosen host's Pilot, move it to the ground arena as an
// exhausted unit (owner's arena), then the event's controller draws. (Move/attach subsystem.)
$customDQHandlers["JTL_126#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $host = GetZoneObject($lastDecision);
    if ($host === null || !empty($host->removed)) return;
    $idx = _SWUFindPilotSubcard($host);
    if ($idx === null) return;
    SWUMoveUpgradeToUnit($lastDecision, $idx, 'GroundArena', true);
    DoDrawCard(intval($player), 1);
};

// ── JTL_038 Corvus — When Played: You may attach a friendly Pilot unit to this. (Defeat all upgrades on
// that Pilot and remove all damage from it.) Offers friendly Pilot UNITS (either arena); the chosen unit
// becomes a Pilot upgrade on Corvus via SWUMoveUnitToUpgrade (normal upgrades defeated, damage cleared,
// captives carried). Param carries Corvus's mzID. (Pilot-UPGRADE relocation from another vehicle: TODO.)
$whenPlayedAbilities["JTL_038:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            if ($mz === $mzID) continue; // not Corvus itself
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            // A friendly Pilot UNIT, or a friendly Vehicle that HOSTS a pilot upgrade (relocate that pilot).
            if (HasTrait($o->CardID, 'Pilot') || _SWUFindPilotSubcard($o) !== null) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Attach_a_friendly_Pilot_to_this", "Attach_a_friendly_Pilot_to_this", "JTL_038#0|" . $mzID);
};

$customDQHandlers["JTL_038#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $corvusMz = $parts[0] ?? '';
    $chosen = GetZoneObject($lastDecision);
    $corvus = GetZoneObject($corvusMz);
    if ($chosen === null || !empty($chosen->removed) || $corvus === null || !empty($corvus->removed)) return;
    $pilotIdx = _SWUFindPilotSubcard($chosen);
    if ($pilotIdx !== null) {
        SWURelocatePilotSubcard($lastDecision, $pilotIdx, $corvusMz); // chose a Vehicle → move its pilot upgrade
    } else {
        SWUMoveUnitToUpgrade($lastDecision, $corvusMz, true);          // chose a Pilot unit → it becomes the upgrade
    }
};

// ── Defeat-replacement resolution (JTL_049 L3-37): the controller chose YES → pick a friendly pilot-less
// Vehicle and attach the would-be-defeated unit to it; NO (or no target) → the defeat happens for real.
$customDQHandlers["DEFEAT_REPLACE"] = function($player, $parts, $lastDecision) {
    $ctrl = intval($parts[0] ?? $player);
    $uid  = intval($parts[1] ?? 0);
    global $playerID; $playerID = $ctrl;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    if ($lastDecision !== "YES") { SWUDefeatUnit($ctrl, $mz, true); return; }
    $vehicles = _SWUPilotlessFriendlyVehicleMzs($ctrl, $uid);
    if (empty($vehicles)) { SWUDefeatUnit($ctrl, $mz, true); return; }
    SWUQueueChooseTarget($ctrl, $vehicles, "Attach_to_a_friendly_Vehicle", "DEFEAT_REPLACE_ATTACH|{$uid}");
};

$customDQHandlers["DEFEAT_REPLACE_ATTACH"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $uid = intval($parts[0] ?? 0);
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    SWUMoveUnitToUpgrade($mz, $lastDecision, true);
};

// JTL_094 Luke (pilot UPGRADE defeat-replacement): YES → rebuild him as an exhausted ground unit from
// the snapshot (host is already gone), preserving UID + any carried captives; NO → discard him.
$customDQHandlers["DEFEAT_REPLACE_UPG"] = function($player, $parts, $lastDecision) {
    global $gReplaceSnapshots, $playerID;
    $uid = intval($parts[1] ?? 0);
    $e = $gReplaceSnapshots[$uid] ?? null;
    unset($gReplaceSnapshots[$uid]);
    if ($e === null) return;
    $cardID = $e['cardID'];
    $owner  = intval($e['owner'] ?? $player);
    if ($lastDecision !== "YES") { SWUAddToDiscard($owner, $cardID, 'PLAY'); return; }
    $caps  = is_array($e['captives'] ?? null) ? array_values($e['captives']) : [];
    $saved = $playerID; $playerID = $owner;
    AddGroundArena($owner, CardID:$cardID, Status:0, Owner:$owner, Damage:0,
        Controller:intval($e['controller'] ?? $owner), Subcards:$caps, UniqueID:$uid);
    $playerID = $saved;
};

// ── JTL_168 Insurgent Saboteurs — Saboteur (auto) + On Attack: You may defeat an upgrade. ────────────
$onAttackAbilities["JTL_168:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueDefeatUpgrade(intval($player), "You_may_defeat_an_upgrade", may: true, max: 1, min: 0);
};

// ── JTL_175 System Shock (thenHandler) — after defeating the upgrade, deal 1 to its host unit. ────────
$customDQHandlers["JTL_175#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $host = (string)($parts[0] ?? ''); // host mzID passed by DEFEAT_UPGRADE#1
    if ($host === '') return; // no upgrade defeated (fizzle) → no damage
    $o = GetZoneObject($host);
    if ($o === null || !empty($o->removed)) return;
    SWUDealDamageToUnit($host, 1, intval($player));
};

// ── JTL_230 Electromagnetic Pulse (event continuation) — deal 2 to the chosen unit, then exhaust it
// (if it survived). ──────────────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_230#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    if ($uid !== 0) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') OnExhaustCard(intval($player), $mz);
    }
};

// ── JTL_091 Apology Accepted (event continuation) — friendly defeated ($lastDecision); you may then
// give 2 Experience tokens to a unit. ────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_091#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_2_Experience_tokens_to_a_unit", "Give_2_Experience_tokens", "GIVE_EXPERIENCE|2");
};

// ── JTL_104 Raddus — conditional Sentinel (in KeywordEffects) + When Defeated: deal damage equal to
// this unit's power to an enemy unit. ────────────────────────────────────────────────────────────────
$whenDefeatedAbilities["JTL_104:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $power = ($self !== null) ? ObjectCurrentPower($self) : 0;
    if ($power <= 0) $power = intval(CardPower('JTL_104'));
    $targets = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Deal_Raddus's_power_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|" . $power);
};

// ── JTL_040 Fleet Interdictor — Sentinel (auto) + When Defeated: may defeat a space unit ≤3 cost. ────
$whenDefeatedAbilities["JTL_040:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(ZoneSearch("mySpaceArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_defeat_a_space_unit_costing_3_or_less", "Defeat_a_space_unit", "DEFEAT_UNIT");
};

// ── JTL_041 Annihilator — When Played/When Defeated: may defeat an enemy unit, then search its
// controller's deck AND hand for every card with that unit's name and discard them (they shuffle). ──
$whenPlayedAbilities["JTL_041:0"] = $whenDefeatedAbilities["JTL_041:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_defeat_an_enemy_unit_(name-hunt_their_deck_and_hand)", "Defeat_an_enemy_unit", "JTL_041#0");
};
$customDQHandlers["JTL_041#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $controller = intval($o->Controller ?? 0);
    if ($controller <= 0) $controller = GetOpponent(intval($player));
    $name = CardTitle($o->CardID);
    SWUDefeatUnit(intval($player), $lastDecision);
    // "Search its controller's deck and hand …" — reveal both searched zones to the active player as
    // information-only OK popups. Queue them NOW (pre-discard/pre-shuffle) so each snapshot shows the
    // full zone that was searched, including the copies about to be discarded. Only reached when a unit
    // was actually defeated; declining the "may" returns above, so no peek without a defeat.
    AddGameLogEntry('REVEAL', "P" . intval($player) . " searched P{$controller}'s hand and deck for " . $name . " (Annihilator)", 'ALL');
    SWUQueueShowOpponentHand(intval($player));
    SWUQueueShowOpponentDeck(intval($player));
    // Name-hunt the controller's hand + deck (discard every card sharing the defeated unit's name).
    $hand = &GetHand($controller);
    foreach ($hand as $h) {
        if (!empty($h->removed)) continue;
        if (CardTitle($h->CardID) === $name) { $h->Remove(); SWUAddToDiscard($controller, $h->CardID, 'HAND'); }
    }
    $deck = &GetDeck($controller);
    foreach ($deck as $c) {
        if (!empty($c->removed)) continue;
        if (CardTitle($c->CardID) === $name) { $c->Remove(); SWUAddToDiscard($controller, $c->CardID, 'DECK'); }
    }
    DecisionQueueController::CleanupRemovedCards();
    $deck2 = &GetDeck($controller);
    EngineShuffle($deck2, true);
};

// ── JTL_055 You're All Clear, Kid (event continuation) — defeat the chosen space unit; if the opponent
// then controls no space units, may give an Experience token to a unit. ─────────────────────────────
$customDQHandlers["JTL_055#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    if (empty(ZoneSearch("theirSpaceArena", AnyUnitFilter))) {
        $targets = array_merge(
            ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
            ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
        );
        if (!empty($targets)) {
            SWUQueueMayChooseTarget(intval($player), $targets,
                "You_may_give_an_Experience_token_to_a_unit", "Give_an_Experience_token", "GIVE_EXPERIENCE|1");
        }
    }
};

// ── JTL_173 Fight Fire With Fire (event) — friendly chosen ($lastDecision); pick the same-arena enemy,
// then deal 3 to each. ───────────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_173#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $arena = (strpos($lastDecision, 'Space') !== false) ? 'Space' : 'Ground';
    $enemies = ZoneSearch("their{$arena}Arena", AnyUnitFilter);
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies,
        "Choose_an_enemy_unit_in_the_same_arena", "JTL_173#1|" . $lastDecision);
};
$customDQHandlers["JTL_173#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $friendlyMz = $parts[0] ?? '';
    SWUDealDamageToUnit($lastDecision, 3, intval($player)); // enemy (their arena — defeat shifts only their indices)
    if ($friendlyMz !== '') SWUDealDamageToUnit($friendlyMz, 3, intval($player)); // friendly
};

// ── JTL_176 Shoot Down (event) — deal 3 to the chosen space unit; if defeated, may deal 2 to a base. ──
$customDQHandlers["JTL_176#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
    if ($uid !== 0 && SWUFindMzByUID($uid) === null) { // defeated this way
        SWUQueueMayChooseTarget(intval($player), ['myBase-0', 'theirBase-0'],
            "You_may_deal_2_to_a_base", "Deal_2_to_a_base", "DEAL_BASE_DAMAGE|2");
    }
};

// ── JTL_239 TIE Dagger Vanguard — When Played: You may deal 2 damage to a damaged unit. ──────────────
$whenPlayedAbilities["JTL_239:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_2_to_a_damaged_unit", "Deal_2_to_a_damaged_unit", "DEAL_UNIT_DAMAGE|2");
};

// ── JTL_248 Dilapidated Ski Speeder — When Played: Deal 3 damage to this unit. ───────────────────────
$whenPlayedAbilities["JTL_248:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($mzID, 3, intval($player));
};

// ── JTL_151 Red Five — On Attack: You may deal 2 damage to a DAMAGED unit. ───────────────────────────
$onAttackAbilities["JTL_151:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_2_to_a_damaged_unit", "Deal_2_to_a_damaged_unit", "DEAL_UNIT_DAMAGE|2");
};

// ── JTL_153 Rebellious Hammerhead — When Played: You may deal damage to a unit equal to the number of
// cards in your hand (counted at resolution, after this card has left your hand). ────────────────────
$whenPlayedAbilities["JTL_153:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $amount = count(ZoneSearch("myHand"));
    if ($amount <= 0) return;
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_equal_to_cards_in_hand", "Deal_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $amount);
};

// ── JTL_170 War Juggernaut — When Played: Deal 1 damage to each of ANY NUMBER of units. (The +1/+0
// per damaged unit passive lives in ObjectCurrentPower.) ─────────────────────────────────────────────
$whenPlayedAbilities["JTL_170:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($targets)) return;
    $max = count($targets); // "any number" — effectiveMax = candidate count so Select All shows
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $max . "|" . implode("&", $targets), 1, tooltip: "Deal_1_damage_to_any_number_of_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_170#0", 1);
};
$customDQHandlers["JTL_170#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS' || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUDealDamageToUnit($mz, 1, intval($player));
    }
};

// ── JTL_014 Admiral Trench — When Deployed (deploy-side reveal flow) ─────────────────────────────
// "Reveal the top 4 cards of your deck. An opponent discards 2 of them. Draw 1 of the remaining cards
// and discard the other." Cross-player flow: stage revealed cards in the OPPONENT's TempZone for their
// pick, then in the CONTROLLER's TempZone for the draw. Each cross-player relative-mzID decision is
// queued from a CUSTOM handler (NOT inline in this trigger closure) so $playerID stays the deciding
// player at MZCountChoices time (DispatchTrigger restores $playerID after a trigger closure).
$whenPlayedAbilities["JTL_014:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // Reveal the top 4 (front non-removed) cards and pull them off the deck into a held set.
    $deck = &GetDeck(intval($player));
    $held = [];
    foreach ($deck as $c) {
        if (!empty($c->removed)) continue;
        $held[] = $c->CardID;
        $c->Remove();
        if (count($held) >= 4) break;
    }
    DecisionQueueController::CleanupRemovedCards();
    if (empty($held)) return; // empty deck — nothing to reveal (trigger-resume owns after-action)
    AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . implode(', ', array_map('GameLogCardRef', $held)));
    // Pass owner + the revealed CardIDs through the decision PARAM (player-agnostic) — StoreVariable is
    // scoped to the current player's store, so cross-player handlers can't read vars set under another.
    DecisionQueueController::AddDecision($player, "CUSTOM",
        "JTL_014#1|" . intval($player) . "|" . implode(",", $held), 1);
};

// Stage the revealed cards in the OPPONENT's TempZone and queue their "discard 2" pick. Runs as a
// CUSTOM (no $playerID restore), so $playerID is left = the opponent for the MZMULTICHOOSE validation.
// $parts[0] = owner, $parts[1] = revealed CardIDs (comma-joined).
$customDQHandlers["JTL_014#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $owner = intval($parts[0] ?? 0);
    $held  = ($parts[1] ?? '') !== '' ? explode(",", $parts[1]) : [];
    if (empty($held)) return;
    $opp   = OtherPlayer($owner);
    $temp  = &GetTempZone($opp);
    while (count($temp) > 0) array_pop($temp);
    foreach ($held as $cid) AddTempZone($opp, $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($held); $k++) $tempMZs[] = "myTempZone-" . $k;
    $discardN = min(2, count($held));
    $playerID = $opp; // leave set for MZCountChoices
    DecisionQueueController::AddDecision($opp, "MZMULTICHOOSE",
        $discardN . "|" . $discardN . "|" . implode("&", $tempMZs), 1,
        tooltip: "Discard_2_of_the_revealed_cards");
    DecisionQueueController::AddDecision($opp, "CUSTOM",
        "JTL_014#2|" . $owner . "|" . implode(",", $held), 1);
};

// Opponent's "discard 2" answer ($lastDecision = myTempZone-i&myTempZone-j). $parts[0]=owner,
// $parts[1]=revealed CardIDs. Discard the picks to the owner's discard (From DECK); stage the remaining
// cards in the OWNER's TempZone for the draw pick.
$customDQHandlers["JTL_014#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $owner = intval($parts[0] ?? 0);
    $held  = ($parts[1] ?? '') !== '' ? explode(",", $parts[1]) : [];
    $opp   = OtherPlayer($owner);
    $pickedIdx = [];
    foreach (explode("&", (string)$lastDecision) as $mz) {
        if (preg_match('/myTempZone-(\d+)/', trim($mz), $m)) $pickedIdx[] = intval($m[1]);
    }
    $playerID = $owner;
    foreach ($pickedIdx as $idx) {
        if (isset($held[$idx])) SWUAddToDiscard($owner, $held[$idx], 'DECK');
    }
    // Drain the opponent's TempZone.
    $tmpOpp = &GetTempZone($opp);
    while (count($tmpOpp) > 0) array_pop($tmpOpp);
    DecisionQueueController::CleanupRemovedCards();
    // Remaining = held minus picked.
    $remaining = [];
    foreach ($held as $k => $cid) {
        if (!in_array($k, $pickedIdx, true)) $remaining[] = $cid;
    }
    if (empty($remaining)) return; // nothing left to draw (trigger-resume owns after-action)
    // Stage the remaining cards in the OWNER's TempZone for "draw 1, discard the other".
    $tmpOwn = &GetTempZone($owner);
    while (count($tmpOwn) > 0) array_pop($tmpOwn);
    foreach ($remaining as $cid) AddTempZone($owner, $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($remaining); $k++) $tempMZs[] = "myTempZone-" . $k;
    $playerID = $owner; // leave set for the owner's MZCHOOSE validation
    DecisionQueueController::AddDecision($owner, "MZCHOOSE", implode("&", $tempMZs), 1,
        tooltip: "Draw_1_of_the_remaining_cards_(discard_the_other)");
    DecisionQueueController::AddDecision($owner, "CUSTOM",
        "JTL_014#3|" . $owner . "|" . implode(",", $remaining), 1);
};

// Owner's "draw 1" answer ($lastDecision = myTempZone-N). $parts[0]=owner, $parts[1]=remaining CardIDs.
// Draw the chosen card to hand; discard the rest of the remaining cards (From DECK). Drain TempZone.
$customDQHandlers["JTL_014#3"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $owner = intval($parts[0] ?? $player);
    $playerID = $owner;
    $remaining = ($parts[1] ?? '') !== '' ? explode(",", $parts[1]) : [];
    $drawIdx = -1;
    if (preg_match('/myTempZone-(\d+)/', trim((string)$lastDecision), $m)) $drawIdx = intval($m[1]);
    foreach ($remaining as $k => $cid) {
        if ($k === $drawIdx) {
            AddHand($owner, CardID: $cid);
            AddGameLogEntry('DRAW', 'P' . $owner . ' drew a card');
        } else {
            SWUAddToDiscard($owner, $cid, 'DECK');
        }
    }
    $tmpOwn = &GetTempZone($owner);
    while (count($tmpOwn) > 0) array_pop($tmpOwn);
    DecisionQueueController::CleanupRemovedCards();
};

// ── SOR_137 Fallen Lightsaber — On Attack (granted via upgrade) ──────────────
// "If attached unit is a Force unit, it gains: On Attack: Deal 1 damage to each
// ground unit the defending player controls." $mzID is the host unit's mzID.
$onAttackAbilities["SOR_137:0"] = function($player, $mzID) {
    $unitObj = GetZoneObject($mzID);
    if ($unitObj === null || ($unitObj->removed ?? false)) return;
    if (!HasTrait($unitObj->CardID, 'Force')) return;
    foreach (ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"]) as $tMz) {
        SWUDealDamageToUnit($tMz, 1, $player);
    }
};

// ── SOR_054 Jedi Lightsaber — On Attack (granted via upgrade) ────────────────
// "If attached unit is a Force unit, it gains: On Attack: Give the defender –2/–2
// for this phase." $mzID is the host unit's mzID; the defender is the unit this
// attack is targeting (exposed by ExecuteSWUAttack via SWU_CURRENT_DEFENDER).
// JTL_142 Darth Vader (pilot) — granted "On Attack: You may deal 1 damage to a unit. If a unit is
// defeated this way, you may deal 1 damage to a unit or base." (chain target list includes enemy base.)
$onAttackAbilities["JTL_142:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Deal_1_damage_to_a_unit", "Choose_a_unit", "JTL_142#0");
};
$customDQHandlers["JTL_142#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    if (SWUFindMzByUID($uid) !== null) return;   // target survived → no chain
    // A unit was defeated this way → may deal 1 to a unit or base.
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    $targets[] = 'theirBase-0';
    $targets[] = 'myBase-0';
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_unit_or_base", "Choose_a_target", "JTL_1422#0");
};
$customDQHandlers["JTL_1422#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    if (strpos($lastDecision, 'Base') !== false) {
        $dp = (strpos($lastDecision, 'my') === 0) ? intval($player) : OtherPlayer(intval($player));
        SWUDealDamageToBase(1, $dp, intval($player));
        return;
    }
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
};

// JTL_210 The Mandalorian (pilot) — When played as a unit: Exhaust up to 2 ground units.
$whenPlayedAbilities["JTL_210:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $ground = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) $ground[] = $mz;
    }
    if (empty($ground)) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|2|" . implode("&", $ground), 1, "Exhaust_up_to_2_ground_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_210#0", 1);
};
$customDQHandlers["JTL_210#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $o->Status = 0;
    }
};
// When played as an upgrade: Exhaust an enemy unit in this arena.
$whenPlayedAsUpgradeAbilities["JTL_210:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    $arena = $host->Location ?? 'GroundArena';
    $targets = array_values(ZoneSearch('their' . $arena, AnyUnitFilter));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_an_enemy_unit_in_this_arena", "EXHAUST_UNIT");
};

// JTL_203 Han Solo (pilot) — When played as an upgrade: You may attack with the attached unit. If it's
// the Millennium Falcon, it deals its combat damage before the defender (SHOOT_FIRST marker).
$whenPlayedAsUpgradeAbilities["JTL_203:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed) || intval($host->Status) !== 1) return; // must be ready
    $uid = intval($host->UniqueID ?? 0);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Attack_with_the_attached_unit?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_203#0|{$uid}", 1);
};
$customDQHandlers["JTL_203#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    $obj = GetZoneObject($mz);
    if ($obj === null || !empty($obj->removed)) return;
    if (CardTitle($obj->CardID ?? '') === 'Millennium Falcon') AddTurnEffect($mz, 'SHOOT_FIRST');
    BeginSWUAttack(intval($player), $mz);
};

// JTL_215 BoShek (pilot) — When played as an upgrade: Discard 2 cards from your deck. Return each of
// those cards with an odd cost to your hand. (Odd-cost milled cards route straight to hand.)
$whenPlayedAsUpgradeAbilities["JTL_215:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < 2; $i++) {
        $idx = _SWUTopDeckFrontIdx(intval($player));
        if ($idx === -1) break;
        $cid = $deck[$idx]->CardID;
        $deck[$idx]->removed = true;
        if ((intval(CardCost($cid)) % 2) === 1) AddHand(intval($player), CardID: $cid);   // odd → hand
        else SWUAddToDiscard(intval($player), $cid, 'DECK');                                // even → discard
    }
    DecisionQueueController::CleanupRemovedCards();
};

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
    SWUPayCost(intval($player), 2, 0);
    $resUnits = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && HasTrait($o->CardID ?? '', 'Resistance')) $resUnits[] = $mz;
    }
    if (empty($resUnits)) return;
    SWUQueueChooseTarget(intval($player), $resUnits, "Ready_a_Resistance_unit", "READY_UNIT");
};

// JTL_098 Snap Wexley — "When played as a unit / On Attack: the next Resistance card you play this phase
// costs 1 less." (whenPlayed fires only when played as a unit; the upgrade side searches instead.) The
// discount is applied in SWUComputePlayCost and consumed in ActivateCard for the next Resistance card.
$whenPlayedAbilities["JTL_098:0"] = $onAttackAbilities["JTL_098:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_SNAP_DISCOUNT');
};
// When played as an upgrade (Pilot): search the top 5 for a Resistance card, reveal it, and draw it.
$whenPlayedAsUpgradeAbilities["JTL_098:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DoTopDeckSearch(intval($player), 5, fn($c) => HasTrait($c, 'Resistance'), 1);
};

// JTL_197 Anakin Skywalker (pilot) — "When attached unit completes an attack (and survives): You may
// return this upgrade to its owner's hand." (The "survives" gate is the surviving-attacker check in
// CollectAfterAttackTriggers; this only fires for a still-living host.)
$onAttackEndFromUpgradeAbilities["JTL_197"] = function($player, $hostMzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($hostMzID);
    if ($host === null || !empty($host->removed)) return;
    $hostUid = intval($host->UniqueID ?? 0);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Return_Anakin_Skywalker_to_your_hand?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_197#0|' . $hostUid, 1);
};
$customDQHandlers["JTL_197#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $hostUid = intval($parts[0] ?? 0);
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz === null) return;
    SWUReturnUpgradeToHand($hostMz, 'JTL_197');
};

// JTL_148 Frisk (pilot) — Piloting (keyword) + When played as an upgrade: You may defeat an upgrade that
// costs 2 or less. The cost<=2 filter scopes the host enumeration to units bearing a matching upgrade.
$whenPlayedAsUpgradeAbilities["JTL_148:0"] = function($player, $mzID) {
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade_costing_2_or_less",
        may: true, max: 1, filter: 'cost<=2', min: 0);
};

// JTL_096 Blue Leader — Ambush (keyword) + When Played: You may pay 2 resources. If you do, move this
// unit to the ground arena and give it 2 Experience tokens. (It becomes a ground unit.)
$whenPlayedAbilities["JTL_096:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if ($o === null || !empty($o->removed)) return;
    if (SWUResourceCount(intval($player), true) < 2) return; // can't pay → no offer
    $uid = intval($o->UniqueID ?? 0);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1,
        tooltip: "Pay_2_to_move_Blue_Leader_to_the_ground_arena_with_2_Experience?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_096#0|' . $uid, 1);
};
$customDQHandlers["JTL_096#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 2) return;
    $uid = intval($parts[0] ?? 0);
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    SWUPayCost(intval($player), 2, 0);
    $newMz = SWUMoveUnitBetweenArenas($mz, 'GroundArena');
    if ($newMz === '') return;
    for ($i = 0; $i < 2; $i++) DoGiveExperienceToken(intval($player), $newMz);
};

// JTL_189 Boba Fett (pilot) — Shielded (keyword) + When played as an upgrade: You may deal 1 damage to a
// unit (2 instead if the attached unit is a Transport).
$whenPlayedAsUpgradeAbilities["JTL_189:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $amt = ($host !== null && HasTrait($host->CardID ?? '', 'Transport')) ? 2 : 1;
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Deal_{$amt}_damage_to_a_unit", "Choose_a_unit", "DEAL_UNIT_DAMAGE|{$amt}");
};

// JTL_057 Astromech Pilot (pilot) — When played as an upgrade: You may heal 2 damage from a unit.
$whenPlayedAsUpgradeAbilities["JTL_057:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Heal_2_from_a_unit", "Choose_a_unit_to_heal", "HEAL_TARGET|2");
};

// JTL_084 Wingman Victor Two (pilot) — When played as an upgrade: Create a TIE Fighter token.
$whenPlayedAsUpgradeAbilities["JTL_084:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'JTL_T01');
};

// JTL_086 Wingman Victor Three (pilot) — When played as an upgrade: You may give an Experience token to
// another unit.
$whenPlayedAsUpgradeAbilities["JTL_086:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $hostUid = $host ? intval($host->UniqueID ?? 0) : 0;
    $units = [];
    foreach (array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && intval($o->UniqueID ?? 0) !== $hostUid) $units[] = $mz;
    }
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Give_an_Experience_token_to_another_unit", "Choose_a_unit", "GIVE_EXPERIENCE|1");
};

// JTL_066 Trace Martez (pilot) — granted "On Attack: You may heal 2 total damage from any number of
// units." (Implemented as heal up to 2 from one chosen unit — the common case.)
$onAttackAbilities["JTL_066:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Heal_2_from_a_unit", "Choose_a_unit_to_heal", "HEAL_TARGET|2");
};

// JTL_046 Paige Tico (pilot) — granted "On Attack: Give an Experience token to this unit, then deal 1 to it."
$onAttackAbilities["JTL_046:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    DoGiveExperienceToken(intval($player), $mzID);
    SWUDealDamageToUnit($mzID, 1, intval($player));
};

// JTL_048 Cassian Andor (pilot) — granted "On Attack: Discard a card from the defending player's deck.
// If that card costs 3 or less, draw a card."
$onAttackAbilities["JTL_048:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $c = SWUMillTopCard($opp);
    if ($c !== null && intval(CardCost($c)) <= 3) DoDrawCard(intval($player), 1);
};

// JTL_035 Tam Ryvora (pilot) — granted "On Attack: Give an enemy unit in this arena -1/-1 for this phase."
$onAttackAbilities["JTL_035:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    $arena = $host->Location ?? 'GroundArena';            // 'GroundArena' or 'SpaceArena' — "this arena"
    $targets = array_values(ZoneSearch('their' . $arena, AnyUnitFilter));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_enemy_unit_in_this_arena_-1/-1", "APPLY_PHASE_DEBUFF|1|1|JTL_035");
};

$onAttackAbilities["SOR_054:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || ($host->removed ?? false)) return;
    if (!HasTrait($host->CardID ?? '', 'Force')) return;
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    // Only units have stats — a base attack has no unit defender to shrink.
    if (strpos($defenderMz, 'Arena') === false) return;
    $defender = GetZoneObject($defenderMz);
    if ($defender === null || ($defender->removed ?? false)) return;
    SWUApplyPhaseDebuff($defenderMz, 2, 2, 'SOR_054');
};

// SOR_142 Sabine Wren — On Attack: "You may deal 1 damage to the defender or to a base." Attacking a
// base auto-pings that base (the defender IS a base, no choice); attacking a unit offers a may-choose
// between the defender unit and either base.
$onAttackAbilities["SOR_142:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    if (strpos($defenderMz, 'Base') !== false) {
        // Attacking a base → always ping that base.
        $tp = (strpos($defenderMz, 'theirBase') !== false) ? GetOpponent(intval($player)) : intval($player);
        SWUDealDamageToBase(1, $tp);
        return;
    }
    // Attacking a unit → may deal 1 to the defender or a base.
    $targets = [$defenderMz, 'theirBase-0', 'myBase-0'];
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_1_to_the_defender_or_a_base", "Deal_1_damage_to_the_defender_or_a_base", "SOR_142#0");
};

$customDQHandlers["SOR_142#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    if (strpos($lastDecision, 'Base') !== false) {
        $tp = (strpos($lastDecision, 'theirBase') !== false) ? GetOpponent(intval($player)) : intval($player);
        SWUDealDamageToBase(1, $tp);
    } else {
        SWUDealDamageToUnit($lastDecision, 1, intval($player));
    }
};

// SOR_097 Admiral Ackbar — When Played: "You may deal damage to a unit equal to the number of units
// you control in its arena." (Restore 1 is auto-wired.) The amount depends on the CHOSEN target's
// arena, so it is computed at resolution time in a bespoke handler.
$whenPlayedAbilities["SOR_097:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_equal_to_your_units_in_its_arena",
        "Choose_a_unit_to_damage", "SOR_097#0");
};

$customDQHandlers["SOR_097#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    // "units you control in its arena" — count the player's own units in the target's arena.
    $myZone = (strpos($lastDecision, 'SpaceArena') !== false) ? "mySpaceArena" : "myGroundArena";
    $count = count(ZoneSearch($myZone, AnyUnitFilter));
    if ($count <= 0) return;
    SWUDealDamageToUnit($lastDecision, $count, intval($player));
};

// SOR_158 Jedha Agitator — On Attack: "If you control a leader unit, deal 2 damage to a ground unit
// or a base." (Saboteur is auto-wired.) Mandatory choose among all ground units + both bases.
$onAttackAbilities["SOR_158:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ["myBase-0", "theirBase-0"]
    );
    SWUQueueChooseTarget(intval($player), $targets,
        "Deal_2_damage_to_a_ground_unit_or_a_base", "SOR_158#0");
};

$customDQHandlers["SOR_158#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    if (strpos($lastDecision, 'Base') !== false) {
        $tp = (strpos($lastDecision, 'theirBase') !== false) ? GetOpponent(intval($player)) : intval($player);
        SWUDealDamageToBase(2, $tp);
    } else {
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
};

// Universal "deal N damage to the chosen base" — param DEAL_BASE_DAMAGE|N, chosen mzID = myBase-0/theirBase-0.
// No-ops on a '-' decline (used with MZMAYCHOOSE). Reference: SOR_143 Fighters for Freedom.
$customDQHandlers["DEAL_BASE_DAMAGE"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $amount = intval($parts[0] ?? 1);
    $tp = (strpos($lastDecision, 'theirBase') !== false) ? GetOpponent(intval($player)) : intval($player);
    SWUDealDamageToBase($amount, $tp);
};

// SOR_196 Chewbacca (unit) — "When this unit is attacked: Ready him." First implemented On Defense
// ability (CR 15.c: "When this unit is attacked" = the On Defense window). Sentinel is auto-wired.
// The OnDefense mzID is already converted to this controller's frame in CombatLogic, so OnReadyCard
// readies Chewbacca (the defender), not the attacker. Mandatory + automatic (no "may", no decision).
$onDefenseAbilities["SOR_196:0"] = function($player, $mzID) {
    OnReadyCard(intval($player), $mzID);
};

// SOR_156 Benthic "Two Tubes" — On Attack: "Another friendly [Aggression] unit gains Raid 2 for this
// phase." Aggression is an ASPECT (CardAspect), not a trait. Mandatory choose among friendly
// Aggression units (excluding self); fizzles if none.
$onAttackAbilities["SOR_156:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $selfUID = ($host !== null) ? intval($host->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? 0) === $selfUID) continue;
        if (strpos(CardAspect($o->CardID) ?? '', 'Aggression') !== false) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Grant_Raid_2_to_another_friendly_Aggression_unit", "SOR_156#0");
};

$customDQHandlers["SOR_156#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, "SOR_156"); // CardID token; Raid value 2 comes from the registry, this phase
};

// SOR_160 Wolffe — When Played / On Attack: "Bases can't be healed for this phase." A global lock
// (affects all bases); checked in OnHealBase, cleared at RegroupPhaseStart. No decision/target.
$whenPlayedAbilities["SOR_160:0"] = $onAttackAbilities["SOR_160:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_NOHEAL_BASE');
};

// SOR_102 Home One — When Played: "Play a [Heroism] unit from your discard pile. It costs 3 less."
// (Restore 2 + the Restore-1 grant are keyword-wired.) Choose a Heroism unit in own discard → play it
// at a 3-cost discount via SWUPlayDiscardUnitDiscounted.
$whenPlayedAbilities["SOR_102:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // Available ready resources AFTER Home One itself was paid for — the discount play must still
    // be affordable, so only offer Heroism units whose discounted (-3) cost can actually be paid.
    // Without this, the UI lets the player pick an unaffordable unit that then fizzles on payment.
    $ready = 0;
    foreach (GetResources(intval($player)) as $r) {
        if (empty($r->removed) && intval($r->Status) === 1) $ready++;
    }
    $targets = [];
    foreach (ZoneSearch('myDiscard', NonLeaderUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (strpos(CardAspect($o->CardID) ?? '', 'Heroism') === false) continue;
        $cost = max(0, intval(CardCost($o->CardID)) + SWUAspectPenalty(intval($player), $o->CardID) - 3);
        if ($cost > $ready) continue; // can't afford after the -3 discount → not a legal target
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Play_a_Heroism_unit_from_your_discard_(costs_3_less)", "SOR_102#0");
};

$customDQHandlers["SOR_102#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $idx = intval(substr($lastDecision, strrpos($lastDecision, '-') + 1));
    SWUPlayDiscardUnitDiscounted(intval($player), $idx, 3);
};

// SOR_075 It Binds All Things (event) — target chosen; "Heal UP TO 3" lets the player pick how much
// (0..min(3, the unit's damage)) via NUMBERCHOOSE, so they can heal less than 3 (and thus deal less).
// An undamaged target has nothing to heal → skip.
$customDQHandlers["SOR_075#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $cap = min(3, intval($obj->Damage ?? 0));
    if ($cap <= 0) return;               // undamaged → "heal up to 3" heals 0, no deal
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|" . $cap, 1, "Heal_how_much_(up_to_3)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SOR_075#1|" . $lastDecision, 1);
};

// Heal the chosen amount (clamped to the unit's damage, max 3), then — if a Force unit is controlled
// and >0 was healed — may deal THAT MUCH to ANOTHER unit. "Deal that much" = the actual amount healed.
$customDQHandlers["SOR_075#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $targetMz = $parts[0] ?? '';
    if ($targetMz === '') return;
    $obj = GetZoneObject($targetMz);
    if ($obj === null || !empty($obj->removed)) return;
    // Validate the scripted answer against the real cap (the harness does not enforce NUMBERCHOOSE max).
    $healed = max(0, min(intval($lastDecision), min(3, intval($obj->Damage ?? 0))));
    if ($healed <= 0) return;            // chose 0 → no heal, no deal
    $healedUID = intval($obj->UniqueID ?? 0);
    OnHealUnit(intval($player), $targetMz, $healed);
    if (!_SWUControlsForceUnit(intval($player))) return;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? 0) === $healedUID) continue; // "another unit"
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_" . $healed . "_damage_to_another_unit",
        "Choose_another_unit_to_damage", "DEAL_UNIT_DAMAGE|" . $healed);
};

// SOR_055 The Force Is With Me (event) — give the chosen friendly unit 2 Experience, a Shield if a
// Force unit is controlled, then offer an optional attack with it. The chosen mzID rides through the
// pipe-delimited CUSTOM param to the attack follow-up.
$customDQHandlers["SOR_055#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $mz = $lastDecision;
    $obj = GetZoneObject($mz);
    DoGiveExperienceToken(intval($player), $mz);
    DoGiveExperienceToken(intval($player), $mz);
    if (_SWUControlsForceUnit(intval($player))) DoGiveShieldToken(intval($player), $mz);
    // YESNO prompt text lives in the TOOLTIP (param "-"); the client renders Tooltip (underscores→
    // spaces), else falls back to "Please choose Yes or No:". Resolve the unit's title from its CardID.
    $title = ($obj !== null) ? CardTitle($obj->CardID ?? '') : '';
    $prompt = ($title !== '') ? "Attack_with_" . str_replace(' ', '_', $title) . "?" : "Attack_with_the_chosen_unit?";
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip:$prompt);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SOR_055#1|" . $mz, 1);
};

$customDQHandlers["SOR_055#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return; // optional attack declined → event cleanup handles after-action
    global $playerID;
    $playerID = intval($player);
    BeginSWUAttack(intval($player), $parts[0]);
};

// SOR_168 Precision Fire — chosen attacker gains Saboteur for this attack (registry GRANT_KEYWORD,
// attack duration), +2/+0 if it's a Trooper (one-shot attack bonus), then attacks. BeginSWUAttack owns
// the after-action; only the no-attacker safety path closes it.
$customDQHandlers["SOR_168#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($obj === null || !empty($obj->removed)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    AddTurnEffect($mz, "SOR_168");                                  // Saboteur for this attack
    if (HasTrait($obj->CardID, 'Trooper')) SWUAddAttackPowerBonus($mz, 2);
    BeginSWUAttack(intval($player), $mz);
    $playerID = $savedPID;
};

// SOR_150 Heroic Sacrifice — +2/+0 for this attack, mark the attacker so that "when it deals combat
// damage" it is defeated (checked in SWUCollectCombatHitTriggers), then attack.
$customDQHandlers["SOR_150#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if ($obj === null || !empty($obj->removed)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($mz, 2);
    AddTurnEffect($mz, "SOR_150");                                  // self-defeat-on-combat-damage marker
    BeginSWUAttack(intval($player), $mz);
    $playerID = $savedPID;
};

// SOR_045 Yoda — When Defeated: "Choose any number of players. They each draw a card." 2-player: a
// 3-way choice (You / Opponent / Both). (Twin Suns multiplayer will use per-player checkboxes later.)
$whenDefeatedAbilities["SOR_045:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "You&Opponent&Both", 1, "Choose_who_draws_a_card");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "YODA_DRAW", 1);
};

$customDQHandlers["YODA_DRAW"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    if ($lastDecision === 'You'      || $lastDecision === 'Both') DoDrawCard(intval($player), 1);
    if ($lastDecision === 'Opponent' || $lastDecision === 'Both') DoDrawCard($opp, 1);
};

// SOR_179 Boba Fett — On Attack: if attacking an EXHAUSTED unit that didn't enter play this round,
// deal 3 damage to the defender. "Entered play this round" = the SWU_PLAYED_UNIT_{uid} flag (set on
// entry, cleared at regroup) on the defender's controller.
$onAttackAbilities["SOR_179:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || ($host->removed ?? false)) return;
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    if (strpos($defenderMz, 'Arena') === false) return; // base attack — no unit defender
    $defender = GetZoneObject($defenderMz);
    if ($defender === null || ($defender->removed ?? false)) return;
    if (intval($defender->Status) !== 0) return; // defender must be EXHAUSTED
    $defUID  = intval($defender->UniqueID ?? 0);
    $defCtrl = intval($defender->Controller ?? GetOpponent(intval($player)));
    if ($defUID > 0 && GlobalEffectCount($defCtrl, 'SWU_PLAYED_UNIT_' . $defUID) > 0) return; // entered this round
    SWUDealDamageToUnit($defenderMz, 3, intval($player));
};

// ── SOR_116 Steadfast Battalion — On Attack ─────────────────────────────────
// "If you control a leader unit, give a friendly unit +2/+2 for this phase."
// The condition is a deployed friendly leader; the buff may target any friendly
// unit (including this attacker). $mzID is the attacker's mzID.
$onAttackAbilities["SOR_116:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ));
    if (empty($targets)) return;
    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_a_friendly_unit_to_give_+2/+2');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'APPLY_PHASE_BUFF|2|2|SOR_116', 1);
};

// ── SOR_106 Attack Pattern Delta — chained per-buff resolver ────────────────
// Applies the current +power/+hp buff to the unit at $lastDecision, then queues the
// next descending buff against a DISTINCT remaining friendly unit (excluding any
// already buffed). No units leave play, so captured mzIDs stay valid across steps.
// Param: SOR_106|curPower|curHp|remainingBuffsCSV|excludedMzIDsCSV
//   remainingBuffsCSV: e.g. "2_2,1_1"   excludedMzIDsCSV: e.g. "myGroundArena-0,myGroundArena-1"
$customDQHandlers["SOR_106#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);

    $chosen = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? '' : $lastDecision;
    if ($chosen !== '') {
        SWUApplyPhaseBuff($chosen, intval($parts[0] ?? 0), intval($parts[1] ?? 0), 'SOR_106');
    }

    // Build the set of units already buffed (prior excludes + the one just chosen).
    $excluded = array_values(array_filter(explode(',', $parts[3] ?? '')));
    if ($chosen !== '') $excluded[] = $chosen;

    // Remaining descending buffs to assign.
    $remaining = array_values(array_filter(explode(',', $parts[2] ?? '')));
    if (empty($remaining)) return;
    $next = array_shift($remaining);            // "2_2"
    [$np, $nh] = array_pad(explode('_', $next), 2, '0');

    // Distinct friendly units that have not yet received a buff.
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), fn($mz) => !in_array($mz, $excluded, true)));
    if (empty($targets)) return;               // not enough friendly units → fizzle

    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Choose_another_friendly_unit_to_give_+' . intval($np) . '/+' . intval($nh));
    }
    $remStr = implode(',', $remaining);
    $excStr = implode(',', $excluded);
    DecisionQueueController::AddDecision($player, 'CUSTOM',
        "SOR_106#0|{$np}|{$nh}|{$remStr}|{$excStr}", 1);
};

// ── SOR_217 Shoot First ─────────────────────────────────────────────────────
// Event effect lives in CardEffects.php (OnPlayEvent). Only the DQ step that
// receives the chosen attacker and launches the attack needs a handler here.

// Receives the chosen attacker mzID from $lastDecision; applies Shoot First's TWO effects then
// starts the attack via BeginSWUAttack. "Shoot First" is colloquially just the deal-first ordering,
// but the SOR_217 card ALSO grants +1/+0 — so we split it: a SOR_217 STAT_BUFF (the +1/+0, shown in
// Active Effects with provenance, folded into ObjectCurrentPower) PLUS the SHOOT_FIRST marker (the
// "deals combat damage before the defender" ordering, read in SWUCombatDamage).
$customDQHandlers["SHOOT_FIRST_ATTACK"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;

    $attackerMzID = $lastDecision ?? '';
    if (empty($attackerMzID) || !str_contains($attackerMzID, '-')) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }

    $attacker = GetZoneObject($attackerMzID);
    if ($attacker === null || !empty($attacker->removed)) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }

    SWUApplyPhaseBuff($attackerMzID, 1, 0, 'SOR_217');   // +1/+0 (registry STAT_BUFF, provenance)
    AddTurnEffect($attackerMzID, 'SHOOT_FIRST');          // deals combat damage before the defender
    BeginSWUAttack($player, $attackerMzID);

    $playerID = $savedPID;
};

// Universal: the deciding player discards the card they chose ($lastDecision = "myHand-N") from
// their OWN hand to their discard (From=HAND). Queued per choice by SWUDiscardCards. Used by any
// "discard N cards" effect (SHD_181 Pillage, SOR_175 Forced Surrender, …). The optional $parts[0]
// is the discarding player (it equals the decision player, so it's redundant but kept explicit).
$customDQHandlers["DISCARD_FROM_OWN_HAND"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $targetPlayer = intval($parts[0] ?? $player);
    $savedPID     = $playerID;
    $playerID     = $targetPlayer;

    $obj = GetZoneObject($lastDecision);
    if ($obj !== null && !($obj->removed ?? false)) {
        $obj->Remove();
        SWUAddToDiscard($targetPlayer, $obj->CardID, 'HAND');
    }
    $playerID = $savedPID;
};

// ── SHD_135 Kylo's TIE Silencer — On Discard ────────────────────────────────
// "Action: If this unit was discarded from your hand or deck this phase, play
//  it from your discard pile (paying its cost)."
// Tags the discard entry with TPP so SWUPlayFromDiscard allows it at cost.
global $cardDiscardedHandlers;
$cardDiscardedHandlers['SHD_135:0'] = function(int $player, object $entry): void {
    if ($entry->From === 'HAND' || $entry->From === 'DECK') {
        $entry->Modifier = 'TPP';
    }
};

// ── JTL_221 Stolen AT-Hauler — When Defeated ────────────────────────────────
// "When Defeated: Choose an opponent. For this phase, they may play this unit
//  from its owner's discard pile for free."
// In 2-player mode "choose an opponent" auto-resolves to the single opponent.
// If controller's opponent is the owner (card was stolen), owner gets TPF (own
// discard). Otherwise opponent gets OTPF (opponent's discard). Uses
// cardDiscardedHandlers (synchronous) so modifier is set before any DQ drain.
$cardDiscardedHandlers['JTL_221:0'] = function(int $player, object $entry, ?object $sourceObject = null): void {
    if ($entry->From === 'PLAY') {
        $controller = intval($sourceObject->Controller ?? $player);
        $opponent = OtherPlayer($controller);
        $entry->Modifier = ($opponent === $player) ? 'TPF' : 'OTPF';
    }
};

// ── JTL_100 Poe Dameron (unit) — "When played as a unit" ─────────────────────
// "Create an X-Wing token. You may attach this unit as an upgrade to a friendly
//  Vehicle unit without a Pilot on it."
//
// Fires ONLY when JTL_100 enters play as a UNIT (CollectEntryTriggers path).
// When played as a PILOT (upgrade), HasWhenPlayedAsUpgradeAbility(JTL_100)=true
// triggers the no-op $whenPlayedAsUpgradeAbilities["JTL_100:0"] below, which
// returns before falling back to this handler — token does NOT fire on pilot play.
//
// The X-Wing token (JTL_T02, Space, 2/2) is created unconditionally.
// The free-attach is optional (MZMAYCHOOSE): target = friendly Vehicles with
// SWUVehiclePilotCount===0 (strict "0 pilots" per card text; no affordability check).
// On accept → JTL_100 is removed from the arena (without being defeated or discarded)
//             and attached as a Pilot subcard on the chosen Vehicle.
// On decline (AnswerDecision:-) → JTL_100 stays as a unit; token still exists.

// No-op WhenPlayedAsUpgrade handler: prevents the fallback to WhenPlayed when
// JTL_100 is played as a pilot (Piloting keyword path).
$whenPlayedAsUpgradeAbilities["JTL_100:0"] = function($player, $mzID) {
    // Intentional no-op: the "When played as a unit" clause must NOT fire
    // when JTL_100 is attached via its Piloting keyword.
};

// WhenPlayed handler: fires only when JTL_100 enters play as a unit.
$whenPlayedAbilities["JTL_100:0"] = function($player, $mzID) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    // Step 1 — create the X-Wing token (JTL_T02) unconditionally.
    // JTL_T02 is a Space unit (2/2). AddSpaceArena directly (no WhenPlayed triggers
    // on a token; mirrors the SOR_087 free-placement pattern).
    $uid = NextUniqueID();
    AddSpaceArena(intval($player), CardID: 'JTL_T02', Status: 0, Owner: intval($player),
        Damage: 0, Controller: intval($player), UniqueID: $uid);

    // Step 2 — collect free-attach targets: friendly Vehicles with 0 pilots (strict rule).
    $vehicles = array_merge(
        ZoneSearch("myGroundArena", ["Unit", "Leader Unit", "Token Unit"]),
        ZoneSearch("mySpaceArena",  ["Unit", "Leader Unit", "Token Unit"])
    );
    $targets = array_values(array_filter($vehicles, function($vMz) use ($mzID) {
        $obj = GetZoneObject($vMz);
        if ($obj === null || !empty($obj->removed)) return false;
        if ($vMz === $mzID) return false; // exclude JTL_100 itself
        if (!HasTrait($obj->CardID ?? '', 'Vehicle')) return false;
        return SWUPilotCanAttach('JTL_100', $obj, 'freeattach');
    }));

    if (!empty($targets)) {
        // Snapshot JTL_100's UniqueID so the continuation can re-resolve it after attach.
        $poeObj = GetZoneObject($mzID);
        $poeUID = intval($poeObj->UniqueID ?? 0);
        SWUQueueMayChooseTarget(
            intval($player),
            $targets,
            "You_may_attach_Poe_Dameron_to_a_friendly_Vehicle",
            "Choose_a_friendly_Vehicle_without_a_Pilot",
            "JTL_100#0|{$poeUID}"
        );
    }

    $playerID = $savedPID;
};

// JTL_100 free-attach continuation: receives the chosen Vehicle mzID.
// Declines ("-") → no-op (JTL_100 stays as unit, token already in play).
// Accept → remove JTL_100 from its arena and attach as Pilot subcard on the vehicle.
$customDQHandlers["JTL_100#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    $hostMz  = $lastDecision;   // chosen Vehicle mzID from MZMAYCHOOSE
    $poeUID  = intval($parts[0] ?? 0);

    // Re-resolve JTL_100 by its UniqueID (index may have shifted if other enters happened).
    $poeMz = ($poeUID > 0) ? SWUFindMzByUID($poeUID) : null;
    if ($poeMz === null) {
        // JTL_100 is no longer in play (e.g. removed by an effect between queue and resolve).
        $playerID = $savedPID;
        return;
    }

    // Validate the host still exists and still has 0 pilots (strict rule).
    $hostObj = GetZoneObject($hostMz);
    if ($hostObj === null || !empty($hostObj->removed)) {
        $playerID = $savedPID;
        return;
    }
    if (!SWUPilotCanAttach('JTL_100', $hostObj, 'freeattach')) {
        $playerID = $savedPID;
        return;
    }

    // Attach JTL_100 as a Pilot subcard (ignoreCost=true — the free-attach has no cost).
    // _SWUFinalizeUpgradeAttach removes JTL_100 from the arena ($poeMz) and adds it as
    // a subcard on $hostMz with IsPilot=true.
    _SWUFinalizeUpgradeAttach(intval($player), 'JTL_100', $poeMz, $hostMz, 0, true, true);
    $playerID = $savedPID;
};

// ── Pilot-leader "When deployed as an upgrade:" abilities (JTL_003/006/009/017) ───────────────────────
// These fire when the leader deploys as a Pilot (the Pilot branch of SWUDeployLeader →
// _SWUFinalizeUpgradeAttach → CollectWhenPlayedAsUpgradeTriggers). $mzID is the HOST Vehicle's mzID.

// JTL_006 Darth Vader — When deployed as an upgrade: Create 2 TIE Fighter tokens.
$whenPlayedAsUpgradeAbilities["JTL_006:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T01');
    SWUCreateUnitToken(intval($player), 'JTL_T01');
};

// JTL_003 Lando Calrissian — When deployed as an upgrade: You may give a Shield token to a unit in a
// DIFFERENT arena than the host Vehicle.
$whenPlayedAsUpgradeAbilities["JTL_003:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    // Host arena from its mzID; target the OTHER arena (both players' units).
    $otherZones = (strpos($mzID, 'Space') !== false)
        ? ['myGroundArena', 'theirGroundArena']
        : ['mySpaceArena',  'theirSpaceArena'];
    $targets = [];
    foreach ($otherZones as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_a_Shield_to_a_unit_in_a_different_arena", "Choose_a_unit_to_Shield", "GIVE_SHIELD");
};

// JTL_017 Han Solo — When deployed as an upgrade: For each friendly unit or upgrade that has an odd
// cost, ready a resource. (Counts friendly units in play + their non-token upgrades, incl. the
// just-attached leader-pilot itself.)
$whenPlayedAsUpgradeAbilities["JTL_017:0"] = function($player, $mzID) {
    $cnt = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        if (intval(CardCost($u->CardID ?? '')) % 2 === 1) $cnt++;
        foreach (GetUpgradesOnUnit($u) as $up) {
            if (intval(CardCost($up->CardID ?? '')) % 2 === 1) $cnt++;
        }
    }
    if ($cnt > 0) SWUReadyResources(intval($player), $cnt);
};

// JTL_009 Boba Fett — When deployed as an upgrade: Deal up to 4 damage divided as you choose among any
// number of units. MZSPLITASSIGN "up to" mode → the shared SPLIT_DAMAGE resolver.
$whenPlayedAsUpgradeAbilities["JTL_009:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena','mySpaceArena','theirGroundArena','theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN",
        "4|" . implode("&", $targets) . "|UPTO", 1, tooltip:"Divide_up_to_4_damage_among_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SPLIT_DAMAGE", 1);
};

// ── _SWUFinalizeUpgradeAttach — shared pay+attach finisher ───────────────────
// Called by ATTACH_UPGRADE (direct ignoreCost path) and the DROID_PAY ATTACH_UPGRADE
// continuation (after Droid exhaustion). $prepaid = number of Droids already exhausted
// toward the upgrade cost. Pays the real-resource remainder, removes the upgrade from
// hand, attaches it as a Subcard, marks the SOR_061 Guardian charge if applicable, and
// collects WhenPlayedAsUpgrade triggers.
// On payment failure: upgrade stays in hand (rollback is natural — nothing removed
// yet), emits a flash message, and returns immediately.
// NOTE: $playerID must be set correctly by the caller before invoking this.
function _SWUFinalizeUpgradeAttach(
    int    $player,
    string $cardID,
    string $upgradeMz,
    string $hostMz,
    int    $prepaid,
    bool   $ignoreCost,
    bool   $isPilot = false
): void {
    // Re-resolve the host — it must still exist (could have been removed between
    // queuing the Droid-choice and resolution, e.g. opponent removal response).
    $hostObj = GetZoneObject($hostMz);
    if ($hostObj === null || !empty($hostObj->removed)) {
        SetFlashMessage("Target no longer in play.");
        return;
    }

    // Retrieve the upgrade hand object for cost computation and removal.
    $upgradeHandObj = ($upgradeMz !== '') ? GetZoneObject($upgradeMz) : null;
    $upgradeForCost = $upgradeHandObj ?? (object)['CardID' => $cardID];

    if ($ignoreCost) {
        // Free play (e.g. SOR_246 top-deck branch): skip payment entirely.
        // Record 0 paid so TWI_210/LAW_231-style consumers see the correct value.
        $GLOBALS['gLastPlayResourcesPaid'] = 0;
    } else {
        // Pilots pay the Piloting cost (CardPilotingCost + aspect penalty).
        // Normal upgrades pay the host-specific play cost (with host discounts).
        $hostCost = $isPilot
            ? SWUComputePilotCost($player, $upgradeForCost)
            : SWUComputePlayCost($player, $upgradeForCost, $hostObj);
        $paid = SWUPayCost($player, $hostCost, $prepaid);
        if (!$paid) {
            SetFlashMessage("Not enough ready resources (need " . max(0, $hostCost - $prepaid) . ").");
            return;
            // Upgrade remains in hand — rollback is natural (nothing was removed yet).
        }
        // JTL_008 Wedge: consume the one-shot Piloting discount now that the pilot has been paid for
        // (the −1 was already folded into $hostCost above). No-op if no discount is pending.
        if ($isPilot && GlobalEffectCount($player, 'SWU_PILOT_DISCOUNT') > 0) {
            RemoveGlobalEffect($player, 'SWU_PILOT_DISCOUNT');
        }
    }

    // Remove the upgrade from hand on successful payment.
    if ($upgradeHandObj !== null && empty($upgradeHandObj->removed)) {
        $upgradeHandObj->Remove();
    }

    // Attach the upgrade as a Subcard on the chosen host.
    if (!is_array($hostObj->Subcards)) $hostObj->Subcards = [];
    $pilotSub = (object)[
        'CardID'      => $cardID,
        'Owner'       => $player,
        'Controller'  => $player,
        'TurnEffects' => [],
        'IsPilot'     => $isPilot,
    ];
    // A Pilot played as an upgrade gets a stable UniqueID + the "played this phase" marker, so that if it
    // is later moved to a unit (Eject) it still counts as "a unit you played this phase" (Luke SOR_005),
    // and the UID survives the upgrade↔unit transitions (JTL move/attach subsystem).
    if ($isPilot) {
        $pilotSub->UniqueID = NextUniqueID();
        AddGlobalEffects($player, 'SWU_PLAYED_UNIT_' . $pilotSub->UniqueID);
    }
    $hostObj->Subcards[] = $pilotSub;

    // SOR_061 Guardian of the Whills: if the host IS the Guardian and its per-round
    // charge is still unused, spend it (the −1 discount was already applied in SWUComputePlayCost
    // above with the $host param). No claw-back needed — payment is exact.
    // Guard 1: only consume the charge if the upgrade had a printed cost ≥ 1 (the −1 actually
    //          mattered; attaching a 0-cost upgrade would waste the charge for nothing).
    // Guard 2: do NOT consume on a free (ignoreCost) play — the discount was never used.
    $hostUid = intval($hostObj->UniqueID ?? 0);
    if (($hostObj->CardID ?? '') === 'SOR_061'
        && intval(CardCost($cardID)) >= 1
        && !$ignoreCost
        && GlobalEffectCount($player, 'SWU_GUARDIAN_UPG_USED_' . $hostUid) <= 0) {
        AddGlobalEffects($player, 'SWU_GUARDIAN_UPG_USED_' . $hostUid);
    }

    // JTL_202 Black Squadron Scout Wing — host reaction: "When you play an upgrade on this unit, you may
    // attack with it (+1/+0 this attack)." Queued onto the same trigger bag so it rides the flush.
    if (($hostObj->CardID ?? '') === 'JTL_202' && intval($hostObj->Status) === 1) {
        AddTrigger($player, 'JTL_202', 'JTL_202', $hostMz);
    }
    // JTL_101 Red Leader — "When a Pilot upgrade attaches to this unit: Create an X-Wing token."
    if (($hostObj->CardID ?? '') === 'JTL_101' && ($isPilot || HasTrait($cardID, 'Pilot'))) {
        SWUCreateUnitToken($player, 'JTL_T02');
    }
    // JTL_223 Razor Crest — "When a Pilot attaches to this unit: You may return a non-leader unit that
    // costs 2 or less, or an exhausted non-leader unit that costs 4 or less, to its owner's hand."
    if (($hostObj->CardID ?? '') === 'JTL_223' && ($isPilot || HasTrait($cardID, 'Pilot'))) {
        AddTrigger($player, 'JTL_223', 'JTL_223', '');
    }
    $triggered  = CollectWhenPlayedAsUpgradeTriggers($player, $cardID, $hostMz);
    $triggered += CollectOnAttachedTriggers($player, $cardID, $hostMz);
    if ($triggered === 0) {
        DecisionQueueController::CleanupRemovedCards();
        SWUAfterAction($player);
    }
}

// ── ATTACH_UPGRADE — core upgrade attach handler ─────────────────────────────
// Receives the chosen target mzID from MZCHOOSE ($lastDecision), the cardID
// from $parts[0], and the upgrade's hand mzID from $parts[1].
// Pays cost AFTER host selection (host-specific, exact — no claw-back), removes
// the upgrade from hand, attaches it as a Subcard on the host, and collects
// WhenPlayedAsUpgrade / WhenPlayed triggers.
// On payment failure: upgrade stays in hand (natural rollback), action abandoned.
// If SEC_122 Vuutun Palaa is in play, the Droid alt-pay step is offered before
// finalizing payment (routes through SWUOfferDroidPayment → DROID_PAY ATTACH_UPGRADE).
$customDQHandlers["ATTACH_UPGRADE"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID   = $playerID;
    $playerID   = intval($player);
    $cardID     = $parts[0] ?? '';
    $upgradeMz  = $parts[1] ?? '';  // hand mzID of the upgrade card
    $ignoreCost = !empty($parts[2]);  // 1 = free play (e.g. SOR_246 top-deck free branch)
    $isPilot    = !empty($parts[3]);  // 1 = pilot path (SWUComputePilotCost instead of play cost)
    $hostMz     = $lastDecision ?? '';  // chosen host mzID from preceding MZCHOOSE
    if ($cardID === '' || $hostMz === '') {
        $playerID = $savedPID;
        return;
    }

    $hostObj = GetZoneObject($hostMz);
    if ($hostObj === null || !empty($hostObj->removed)) {
        $playerID = $savedPID;
        return;
    }

    // If SEC_122 is in play and there is a non-zero cost with ready Droids, offer
    // the Droid alt-pay step via the central function.
    if (!$ignoreCost) {
        $upgradeHandObj = ($upgradeMz !== '') ? GetZoneObject($upgradeMz) : null;
        $upgradeForCost = $upgradeHandObj ?? (object)['CardID' => $cardID];
        // Pilots pay the Piloting cost (CardPilotingCost + aspect penalty); normal upgrades
        // pay the host-specific play cost (CardCost + aspect penalty + host discounts).
        $hostCost = $isPilot
            ? SWUComputePilotCost(intval($player), $upgradeForCost)
            : SWUComputePlayCost(intval($player), $upgradeForCost, $hostObj);
        // Encode $isPilot as the 4th field so the DROID_PAY continuation can rebuild it.
        $droidArgs = "{$cardID}|{$upgradeMz}|{$hostMz}|" . ($isPilot ? '1' : '0');
        SWUOfferDroidPayment(intval($player), $hostCost, 'ATTACH_UPGRADE', $droidArgs, 0);
        $playerID = $savedPID;
        return;
    }

    // ignoreCost path — finalize directly without SEC_122 check.
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upgradeMz, $hostMz, 0, $ignoreCost, $isPilot);
    $playerID = $savedPID;
};

// ── Piloting play helpers ─────────────────────────────────────────────────────
// SWUQueuePilotVehiclePick: routes a pilot onto a Vehicle target. Called when the
// player has already committed to the Pilot branch (either via OPTIONCHOOSE or the
// pilot-only short-circuit). $vehicles = array of host mzIDs from
// SWUGetPilotValidTargets. _SWUFinalizeUpgradeAttach uses SWUComputePilotCost
// (isPilot=true) instead of SWUComputePlayCost.
//
// If count($vehicles) === 1: auto-attaches to the sole valid Vehicle (skips MZCHOOSE),
//   routing through SWUOfferDroidPayment → _SWUFinalizeUpgradeAttach so Droid alt-pay
//   is still offered when applicable. $playerID is set before calling SWUOfferDroidPayment
//   because that function may queue MZMULTICHOOSE (Droids) and must leave $playerID = $player.
//
// If count($vehicles) >= 2: queues the MZCHOOSE picker as before.
// CRITICAL: $playerID must be left = $player on return so MZCountChoices can
// resolve the relative mzIDs in the MZCHOOSE param immediately after this returns.
function SWUQueuePilotVehiclePick(int $player, string $mzID, string $cardID, array $vehicles): void {
    global $playerID;
    $playerID = $player;

    if (count($vehicles) === 1) {
        // Auto-attach to the only valid Vehicle — no picker needed.
        // Route through SWUOfferDroidPayment so SEC_122 Droid alt-pay is offered if applicable.
        // Args format: "{cardID}|{upgradeMz}|{hostMz}|{isPilot}" (isPilot=1).
        $hostMz   = $vehicles[0];
        $droidArgs = "{$cardID}|{$mzID}|{$hostMz}|1";
        // Compute the pilot cost for the Droid-offer threshold.
        $upgradeObj = GetZoneObject($mzID);
        $upgradeForCost = $upgradeObj ?? (object)['CardID' => $cardID];
        $pilotCost = SWUComputePilotCost($player, $upgradeForCost);
        SWUOfferDroidPayment($player, $pilotCost, 'ATTACH_UPGRADE', $droidArgs, 1);
        // $playerID left = $player by SWUOfferDroidPayment (it sets it before any MZMULTICHOOSE).
        return;
    }

    // 2+ vehicles: show the MZCHOOSE picker.
    DecisionQueueController::AddDecision($player, "MZCHOOSE",
        implode("&", $vehicles), 1, tooltip:"Choose_a_Vehicle_to_pilot");
    // $parts[3] = "1" → pilot path in ATTACH_UPGRADE; $parts[2] = "0" = not ignoreCost.
    DecisionQueueController::AddDecision($player, "CUSTOM",
        "ATTACH_UPGRADE|{$cardID}|{$mzID}|0|1", 1);
}

// PILOT_PLAY_CHOICE — receives the OPTIONCHOOSE "Unit" or "Pilot" answer.
// $parts[0] = hand mzID of the pilot card, $parts[1] = cardID.
// "Pilot" → queue the Vehicle pick; "Unit" → continue as a normal unit play.
$customDQHandlers["PILOT_PLAY_CHOICE"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $mzID   = $parts[0] ?? '';
    $cardID = $parts[1] ?? '';
    if ($lastDecision === 'Pilot') {
        $vehicles = SWUGetPilotValidTargets(intval($player), $cardID);
        if (!empty($vehicles)) {
            SWUQueuePilotVehiclePick(intval($player), $mzID, $cardID, $vehicles);
            return;
        }
    }
    // "Unit" (or no Vehicle left): re-enter the FULL unit-play path including Exploit.
    // Do NOT call SWUContinuePlayAfterExploit here — that skips the Exploit step.
    // _SWUBeginPlayCardUnitPath runs the identical path as a non-pilot unit play.
    // $playerID is already set above; the helper does NOT restore it (see its comment),
    // so $playerID remains = $player on return (correct for any queued MZMULTICHOOSE).
    _SWUBeginPlayCardUnitPath(intval($player), $mzID);
};

// ── Leader deploy-as-Pilot choice handlers ───────────────────────────────────

// LEADER_DEPLOY_CHOICE — receives the OPTIONCHOOSE "Unit" or "Pilot" answer.
// $parts[0] = the leader's CardID (e.g. "JTL_001").
// "Unit"  → call SWUDeployLeader($player, 'Unit') — skips the choose-one gate (no
//            eligible Vehicle left / player chose Unit) via the normal Unit path.
// "Pilot" → re-read eligible Vehicles, auto-attach if exactly one, else queue MZCHOOSE
//            then LEADER_DEPLOY_PILOT to finish the attach.
$customDQHandlers["LEADER_DEPLOY_CHOICE"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $leaderCardID = $parts[0] ?? '';

    if ($lastDecision === 'Pilot') {
        $vehicles = SWUGetLeaderPilotVehicles(intval($player));
        if (!empty($vehicles)) {
            if (count($vehicles) === 1) {
                // Auto-attach to the single eligible Vehicle — no picker needed.
                SWUDeployLeader(intval($player), 'Pilot', $vehicles[0]);
            } else {
                // 2+ vehicles: let the player pick.
                DecisionQueueController::AddDecision($player, "MZCHOOSE",
                    implode("&", $vehicles), 1, tooltip:"Choose_a_Vehicle_to_deploy_onto");
                DecisionQueueController::AddDecision($player, "CUSTOM",
                    "LEADER_DEPLOY_PILOT|{$leaderCardID}", 1);
            }
            return;
        }
        // No eligible Vehicle any more (removed between queue and resolution): fall through to Unit.
    }

    // "Unit" or fallback: deploy normally as a unit.
    // 'UnitDirect' bypasses the choose-one gate so we don't re-offer when vehicles still exist.
    SWUDeployLeader(intval($player), 'UnitDirect');
};

// LEADER_DEPLOY_PILOT — receives the MZCHOOSE host mzID, finalizes the Pilot attach.
// $parts[0] = leaderCardID (informational; SWUDeployLeader re-resolves from the leader zone).
$customDQHandlers["LEADER_DEPLOY_PILOT"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hostMz = $lastDecision ?? '';
    if ($hostMz === '' || $hostMz === '-') return;
    SWUDeployLeader(intval($player), 'Pilot', $hostMz);
};

// JTL_013 — receives the MZCHOOSE host mzID from JTL_013's leader Action 2+-vehicle path.
// Called only when there are ≥2 eligible Vehicles and the player picks one.
$customDQHandlers["JTL_013#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hostMz = $lastDecision ?? '';
    if ($hostMz === '' || $hostMz === '-') {
        SWUAfterAction(intval($player));
        return;
    }
    _SWUFinalizeUpgradeAttach(intval($player), 'JTL_013', '', $hostMz, 0, true, true);
};

// ── Implemented When Played abilities ───────────────────────────────────────

// ASH_259 — LEP Ratcatcher: "When Played: Deal 1 damage to a unit." (any unit)
$whenPlayedAbilities["ASH_259:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", "myGroundArena&mySpaceArena&theirGroundArena&theirSpaceArena", 1, "Choose_a_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEAL_UNIT_DAMAGE|1", 1);
};

// TWI_137 Savage Opress — "When Played: If you control fewer units (including this one) than an opponent, ready this unit."
$whenPlayedAbilities["TWI_137:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $myCount    = count(ZoneSearch("myGroundArena",    NonLeaderUnitFilter)) +
                  count(ZoneSearch("mySpaceArena",     NonLeaderUnitFilter));
    $theirCount = count(ZoneSearch("theirGroundArena", NonLeaderUnitFilter)) +
                  count(ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter));
    if ($myCount < $theirCount) OnReadyCard($player, $mzID);
};

// SOR_215 — Snapshot Reflexes: "When Played: You may attack with the attached unit."
// $mzID is the host unit's arena mzID (e.g. "myGroundArena-0").
$whenPlayedAbilities["SOR_215:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Attack_with_attached_unit?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_215#0|{$player}|{$mzID}", 1);
};

// SOR_215: player answered the "Attack with attached unit?" YESNO.
// $parts[0] = player, $parts[1] = host unit mzID.
global $customDQHandlers;
$customDQHandlers["SOR_215#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $unitMzID = $parts[1] ?? '';

    if ($lastDecision !== "YES" && $lastDecision !== "1") {
        // Declined — SWU_TRIGGER_RESUME handles SWUAfterAction.
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return;
    }

    $unitObj = GetZoneObject($unitMzID);
    if ($unitObj === null || !empty($unitObj->removed)) {
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return;
    }

    // Unit must be ready (Status=1) to attack. BeginSWUAttack handles exhaust + target selection.
    if (intval($unitObj->Status) !== 1) {
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return;
    }

    BeginSWUAttack($player, $unitMzID);

    $playerID = $savedPID;
};

// SOR_226 Admiral Motti — "When Defeated: You may ready a [Villainy] unit."
// Single MZMAYCHOOSE over all Villainy units; READY_UNIT no-ops on a '-' decline.
$whenDefeatedAbilities["SOR_226:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $obj = GetZoneObject($mz);
        if ($obj === null || !empty($obj->removed)) continue;
        if (strpos(CardAspect($obj->CardID) ?? '', 'Villainy') !== false) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Ready_a_Villainy_unit?", "Ready_a_Villainy_unit", "READY_UNIT");
};

// SOR_101 Rogue Squadron Skirmisher — When Played: Return a unit that costs 2 or
// less from your discard pile to your hand. (Mandatory; fizzles if none.)
$whenPlayedAbilities["SOR_101:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $discard  = GetDiscard($player);
    $valid    = [];
    for ($i = 0; $i < count($discard); $i++) {
        $o = $discard[$i];
        if ($o === null || !empty($o->removed)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue; // units only
        if (intval(CardCost($o->CardID)) <= 2) $valid[] = "myDiscard-$i";
    }
    if (empty($valid)) return;
    if (count($valid) === 1) {
        DecisionQueueController::AddDecision($player, "PASSPARAMETER", $valid[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $valid), 1);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_101#0", 1);
};

$customDQHandlers["SOR_101#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};

// ── Deck-mill cards (Phase 8.2): SOR_047 Kanan, SOR_204 Greedo, SOR_188 Chopper ──
// SOR_047 Kanan Jarrus — "On Attack: You may discard 1 card from the defending player's deck for
// each friendly SPECTRE unit. Heal 1 damage from your base for each different aspect among the
// discarded cards." Optional whole effect → YESNO.
$onAttackAbilities["SOR_047:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        'Discard_from_the_defender\'s_deck_per_Spectre,_then_heal_per_aspect?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SOR_047#0', 1);
};

$customDQHandlers["SOR_047#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $defender = GetOpponent(intval($player)); // 2-player: the defending player is the opponent
    $spectre = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (HasTrait($u->CardID, 'Spectre')) $spectre++;
    }
    if ($spectre <= 0) return;
    $aspects = [];
    for ($i = 0; $i < $spectre; $i++) {
        $milled = SWUMillTopCard($defender);
        if ($milled === null) break; // deck empty
        foreach (explode(',', CardAspect($milled) ?? '') as $a) {
            $a = trim($a);
            if ($a !== '') $aspects[$a] = true;
        }
    }
    $distinct = count($aspects);
    if ($distinct > 0) OnHealBase(intval($player), intval($player), $distinct);
};

// SOR_204 Greedo — "When Defeated: You may discard a card from your deck. If it's not a unit, deal
// 2 damage to a ground unit." Optional → YESNO; the discard is the top of the controller's deck.
$whenDefeatedAbilities["SOR_204:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, 'Discard_the_top_of_your_deck?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SOR_204#0', 1);
};

$customDQHandlers["SOR_204#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;
    if (strpos(CardType($milled) ?? '', 'Unit') !== false) return; // a unit → no damage
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ));
    SWUQueueChooseTarget(intval($player), $targets, 'Deal_2_damage_to_a_ground_unit', 'DEAL_UNIT_DAMAGE|2');
};

// SOR_188 Chopper — "On Attack: Discard a card from the defending player's deck. If it's an event,
// exhaust a resource that player controls." (Conditional Raid 1 lives in GetConditionalKeyword_Raid_Value.)
$onAttackAbilities["SOR_188:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defender = GetOpponent(intval($player));
    $milled = SWUMillTopCard($defender);
    if ($milled === null) return;
    if (strpos(CardType($milled) ?? '', 'Event') !== false) {
        SWUExhaustResources($defender, 1); // exhaust a resource the defending player controls
    }
};

// SOR_183 Bounty Hunter Crew — "When Played: You may return an event from a discard pile to its
// owner's hand." Any discard pile (both players'); the event returns to its OWNER's hand.
$whenPlayedAbilities["SOR_183:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $events = array_values(array_merge(
        ZoneSearch('myDiscard',    ['Event']),
        ZoneSearch('theirDiscard', ['Event'])
    ));
    SWUQueueMayChooseTarget(intval($player), $events,
        'Return_an_event_from_a_discard_pile_to_its_owner\'s_hand', 'Choose_an_event_to_return', 'SOR_183#0');
};

$customDQHandlers["SOR_183#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    SWUReturnDiscardCardToOwnerHand(intval($player), $lastDecision);
};

// SOR_197 Lando Calrissian — "When Played: Return up to 2 friendly resources to their owners'
// hands." MZMULTICHOOSE up to 2 of the controller's resources; each returns to its owner's hand.
$whenPlayedAbilities["SOR_197:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $resources = array_values(ZoneSearch('myResources'));
    if (empty($resources)) return;
    $targetStr = implode('&', $resources);
    DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE', "0|2|{$targetStr}", 1,
        'Return_up_to_2_friendly_resources_to_hand');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SOR_197#0', 1);
};

$customDQHandlers["SOR_197#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    // Snapshot the chosen resource objects BEFORE any removal (indices shift on each removal).
    $chosen = [];
    foreach (explode('&', $lastDecision) as $m) {
        $m = trim($m);
        if ($m === '' || $m === '-' || $m === 'PASS') continue;
        $o = GetZoneObject($m);
        if ($o !== null && empty($o->removed)) $chosen[] = $o;
    }
    foreach ($chosen as $o) {
        $owner = intval($o->Owner ?? 0);
        if ($owner <= 0) $owner = intval($player); // unset Owner → the controller (friendly)
        $o->removed = true;
        AddHand($owner, CardID:$o->CardID);
    }
    DecisionQueueController::CleanupRemovedCards();
};

// SOR_056 Bendu — On Attack: arm the one-shot "next non-Heroism/non-Villainy card you play this phase
// costs 2 less" charge (consumed in ActivateCard; the −2 lives in SWUComputePlayCost).
$onAttackAbilities["SOR_056:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_NEUTRAL_DISCOUNT');
};

// SOR_181 Jabba the Hutt — When Played: search the top 8 of your deck for a Trick event, reveal it,
// and draw it. (The "Trick events cost 1 less" passive lives in $playCostFieldModifiers.)
$whenPlayedAbilities["SOR_181:0"] = function($player, $mzID) {
    DoTopDeckSearch(intval($player), 8,
        fn($c) => HasTrait($c, 'Trick') && stripos(CardType($c) ?? '', 'Event') !== false, 1);
};

// SOR_080 General Tagge — When Played: give an Experience token to each of up to
// 3 Trooper units. MZMULTICHOOSE param is "min|max|specs"; result is &-delimited.
$whenPlayedAbilities["SOR_080:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (HasTrait($o->CardID, 'Trooper')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|3|" . $targetStr, 1, tooltip:"Give_Experience_to_up_to_3_Trooper_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_080#0", 1);
};

$customDQHandlers["SOR_080#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === "" || $mz === "-" || $mz === "PASS") continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};

// SOR_235 Galactic Ambition — play the chosen non-Heroism hand unit for free, then deal its PRINTED
// cost to your own base. Capture the cost before playing (the card leaves hand). The turn-state guard
// mirrors SWUPlayTopDeckCard so the nested ActivateCard doesn't double-advance the turn.
$customDQHandlers["SOR_235#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $cost      = intval(CardCost($o->CardID));
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, true);  // free play
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
    SWUDealDamageToBase($cost, intval($player));          // damage to YOUR base = its cost
};

// SOR_138 Force Lightning — the chosen unit loses all abilities this phase (TurnEffect marker read by
// LostAbilities); then, if the caster controls a Force unit, offer "pay any number of resources, deal
// 2 per resource" to that same unit.
$customDQHandlers["SOR_138#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    if (!isset($o->TurnEffects) || !is_array($o->TurnEffects)) $o->TurnEffects = [];
    if (!in_array('SOR_138', $o->TurnEffects, true)) $o->TurnEffects[] = 'SOR_138';
    if (_SWUControlsForceUnit(intval($player))) {
        $maxX = SWUResourceCount(intval($player), readyOnly: true);
        if ($maxX > 0) {
            DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "0|" . $maxX, 1, tooltip:"Pay_any_number_of_resources_(deal_2_damage_each)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_138#1|" . $lastDecision, 1);
        }
    }
};
$customDQHandlers["SOR_138#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $x = intval($lastDecision);
    if ($x <= 0) return;
    if (!SWUExhaustResources(intval($player), $x)) return;   // pay X (NUMBERCHOOSE was capped at ready)
    $targetMz = $parts[0] ?? '';
    if ($targetMz !== '') SWUDealDamageToUnit($targetMz, 2 * $x, intval($player));
};

// SOR_167 Force Throw — pick a player; THAT player discards a card of their choice; then if the CASTER
// controls a Force unit, the caster may deal damage to a unit equal to the discarded card's cost.
// Discard the chosen hand card ($mz, relative to $discarder), then queue the caster's optional damage.
function _SWUForceThrowDiscard(int $discarder, int $caster, string $mz): void {
    global $playerID;
    $playerID = $discarder;
    $o = GetZoneObject($mz);
    if ($o === null || !empty($o->removed)) return;
    $cost   = intval(CardCost($o->CardID));
    $cardID = $o->CardID;
    $o->Remove();
    SWUAddToDiscard($discarder, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', "P{$discarder} discarded " . GameLogCardRef($cardID));
    if ($cost > 0 && _SWUControlsForceUnit($caster)) {
        $playerID = $caster;
        $units = array_merge(
            ZoneSearch("myGroundArena",    AnyUnitFilter),
            ZoneSearch("mySpaceArena",     AnyUnitFilter),
            ZoneSearch("theirGroundArena", AnyUnitFilter),
            ZoneSearch("theirSpaceArena",  AnyUnitFilter)
        );
        SWUQueueMayChooseTarget($caster, $units, "You_may_deal_{$cost}_damage_to_a_unit", "Deal_{$cost}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $cost);
    }
}
$customDQHandlers["SOR_167#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $target = ($lastDecision === 'Opponent') ? OtherPlayer($caster) : $caster;
    // Compact zones first: Force Throw itself is a just-played event still sitting as a removed entry
    // in the caster's hand, which would desync ZoneSearch (skips removed) from GetZoneObject (raw idx).
    DecisionQueueController::CleanupRemovedCards();
    $playerID = $target;
    $hand = array_values(ZoneSearch("myHand"));   // all card types, discarding player's perspective
    if (empty($hand)) { $playerID = $caster; return; }   // no card to discard → nothing happens
    if (count($hand) === 1) {
        // Deterministic — discard it directly (avoids a fragile cross-player auto-resolve).
        _SWUForceThrowDiscard($target, $caster, $hand[0]);
    } else {
        // The DISCARDING player chooses which card; the follow-up runs the caster's optional damage.
        SWUQueueChooseTarget($target, $hand, "Discard_a_card_from_your_hand", "SOR_167#1|" . $caster);
    }
};
$customDQHandlers["SOR_167#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    _SWUForceThrowDiscard(intval($player), intval($parts[0] ?? $player), $lastDecision);
};

// SOR_233 I Am Your Father — the caster picked the enemy unit ($lastDecision). Offer its controller a
// YESNO to refuse the 7 damage; the branch resolves in SOR_233#1. Carry the target by UniqueID so a
// later board change can't stale the mzID.
$customDQHandlers["SOR_233#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $playerID = $caster;
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $uid = intval($o->UniqueID);
    $controller = intval($o->Controller ?? OtherPlayer($caster));
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
        tooltip:"Say_no_to_the_7_damage?_(opponent_draws_3)");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "SOR_233#1|{$caster}|{$uid}", 1);
};
$customDQHandlers["SOR_233#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    $uid    = intval($parts[1] ?? 0);
    if ($lastDecision === "YES") {                 // controller refuses → no damage, caster draws 3
        $playerID = $caster;
        DoDrawCard($caster, 3);
        return;
    }
    $playerID = $caster;                           // controller allows → deal 7 to the unit
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) SWUDealDamageToUnit($mz, 7, $caster);
};

// SOR_187 I Had No Choice — the caster chose up to 2 non-leader units ($lastDecision, &-delimited).
// 0 → no-op; 1 → return it to its owner's hand; 2 → the opponent chooses which is saved (the other is
// buried on the bottom of its owner's deck, resolved in SOR_187#1). Targets carried by UniqueID.
$customDQHandlers["SOR_187#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $playerID = $caster;
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if (count($uids) >= 2) break;
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
        $uids[] = intval($o->UniqueID);
    }
    $uids = array_values(array_unique($uids));
    if (count($uids) === 0) return;
    if (count($uids) === 1) {                       // forced single choice → return to owner's hand
        $mz = SWUFindMzByUID($uids[0]);
        if ($mz !== null) SWUBounceUnit($caster, $mz);
        return;
    }
    // 2 units: the opponent chooses which returns to hand. Resolve the two under the opponent's
    // perspective and queue an MZCHOOSE answered by them; leave $playerID = opponent for MZCountChoices.
    $opp = OtherPlayer($caster);
    $playerID = $opp;
    $mzA = SWUFindMzByUID($uids[0]);
    $mzB = SWUFindMzByUID($uids[1]);
    if ($mzA === null || $mzB === null) {           // one vanished → bounce whatever remains, no choice
        $playerID = $caster;
        foreach ($uids as $u) { $m = SWUFindMzByUID($u); if ($m !== null) SWUBounceUnit($caster, $m); }
        return;
    }
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", $mzA . "&" . $mzB, 1,
        tooltip:"Choose_which_unit_returns_to_hand");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "SOR_187#1|{$caster}|{$uids[0]}|{$uids[1]}", 1);
};
$customDQHandlers["SOR_187#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp    = intval($player);
    $caster = intval($parts[0] ?? 0);
    $uidA   = intval($parts[1] ?? 0);
    $uidB   = intval($parts[2] ?? 0);
    $playerID = $opp;
    $chosenUID = $uidA;
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $chosen = GetZoneObject($lastDecision);
        if ($chosen !== null) $chosenUID = intval($chosen->UniqueID);
    }
    $otherUID = ($chosenUID === $uidA) ? $uidB : $uidA;
    // Return chosen to owner's hand (resolve from the caster's frame), then bury the other.
    $playerID = $caster;
    $mzChosen = SWUFindMzByUID($chosenUID);
    if ($mzChosen !== null) SWUBounceUnit($caster, $mzChosen);
    $mzOther = SWUFindMzByUID($otherUID);
    if ($mzOther !== null) SWUUnitToBottomOfDeck($caster, $mzOther);
};

// SOR_145 K-2SO — "When Defeated: For each opponent, choose one: either deal 3 damage to that player's
// base, or that player discards a card from their hand." 2-player → one opponent; K-2SO's controller
// ($player) chooses via OPTIONCHOOSE. (Iterate opponents for Twin Suns later.)
$whenDefeatedAbilities["SOR_145:0"] = function($player, $mzID) {
    $opp = OtherPlayer(intval($player));
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Base&Discard", 1,
        tooltip:"Deal_3_to_their_base_or_make_them_discard?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SOR_145#0|{$opp}", 1);
};
$customDQHandlers["SOR_145#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $controller = intval($player);
    $opp = intval($parts[0] ?? OtherPlayer($controller));
    $playerID = $controller;
    if ($lastDecision === "Discard") {
        SWUDiscardCards($controller, 1);   // makes OtherPlayer($controller) = $opp discard 1
    } else {
        SWUDealDamageToBase(3, $opp);
    }
};

// SOR_174 Smoke and Cinders — discard every card in $parts[0]'s hand NOT among the kept mzIDs
// ($lastDecision, &-delimited). Snapshot the discard set before any removal (mark removed, then one
// cleanup) so indices stay valid through the loop. See SWUKeepNDiscardRest (which built the spec).
$customDQHandlers["SOR_174#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $p = intval($parts[0] ?? $player);
    $playerID = $p;
    $keptSet = [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        foreach (explode("&", $lastDecision) as $m) { if ($m !== '') $keptSet[$m] = true; }
    }
    $hand = &GetHand($p);
    $toDiscard = [];
    foreach ($hand as $idx => $card) {
        if (isset($card->removed) && $card->removed) continue;
        if (isset($keptSet["myHand-{$idx}"])) continue;
        $toDiscard[] = $card;
    }
    foreach ($toDiscard as $card) {
        $cid = $card->CardID;
        $card->removed = true;
        SWUAddToDiscard($p, $cid, 'HAND');
    }
    DecisionQueueController::CleanupRemovedCards();
};

// ── SOR_223 Don't Get Cocky — iterative reveal-until-stop ────────────────────
// Reveal the top card of $player's deck (public), removing it from the deck and returning its CardID
// (null if the deck is empty). Revealed cards are held by CardID in the loop param and returned to the
// deck bottom at resolution.
function _SOR223RevealTop(int $player): ?string {
    $deck = &GetDeck($player);
    if (count($deck) === 0) return null;
    $card = array_shift($deck);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    $cid = $card->CardID;
    AddGameLogEntry('REVEAL', "P{$player} revealed " . GameLogCardRef($cid));
    return $cid;
}
// Reveal one more card, then either continue (queue the YESNO) or resolve (stopped / 7 revealed / deck
// empty). $revealed = the CardIDs revealed so far (this call appends one).
function _SOR223Step(int $player, int $targetUID, array $revealed): void {
    $cid = _SOR223RevealTop($player);
    if ($cid === null) { _SOR223Resolve($player, $targetUID, $revealed); return; }  // deck already empty
    $revealed[] = $cid;
    $deckEmpty = (count(GetDeck($player)) === 0);
    if (count($revealed) >= 7 || $deckEmpty) {            // hard cap / out of cards → resolve now
        _SOR223Resolve($player, $targetUID, $revealed);
        return;
    }
    $revealedStr = implode(",", $revealed);
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Reveal_another_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_223#1|{$targetUID}|{$revealedStr}", 1);
}
// Resolve: if combined cost ≤ 7 (and > 0) deal it to the chosen unit; return revealed cards to the
// bottom of the deck in random order.
function _SOR223Resolve(int $player, int $targetUID, array $revealed): void {
    global $playerID;
    $playerID = intval($player);
    $total = 0;
    foreach ($revealed as $cid) $total += intval(CardCost($cid));
    if ($total > 0 && $total <= 7) {
        $mz = SWUFindMzByUID($targetUID);
        if ($mz !== null) SWUDealDamageToUnit($mz, $total, $player);
    }
    if (!empty($revealed)) _topDeckPutRemainingToBottom($player, $revealed);  // shuffles → bottom
}
$customDQHandlers["SOR_223#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    _SOR223Step(intval($player), intval($o->UniqueID), []);
};
$customDQHandlers["SOR_223#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $revealed = (($parts[1] ?? '') !== '') ? explode(",", $parts[1]) : [];
    if ($lastDecision === "YES") _SOR223Step(intval($player), $uid, $revealed);  // reveal another
    else                        _SOR223Resolve(intval($player), $uid, $revealed); // stop
};

// ── "Choose two, in any order" modal (SOR_058/107/155/203) ───────────────────
// Resolve ONE chosen mode (queues that mode's own effect decisions at block 1). All-units helper used
// by several modes.
function _SWUAllUnits(): array {
    return array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter), ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
}
function _SWUModalResolveMode(int $player, string $cardID, string $label): void {
    global $playerID;
    $playerID = intval($player);
    if ($cardID === 'SOR_058') {                         // Vigilance
        switch ($label) {
            case 'Discard6':                              // discard 6 from an opponent's deck
                for ($i = 0; $i < 6; $i++) SWUMillTopCard(OtherPlayer($player));
                return;
            case 'Heal5':                                 // heal 5 from a base
                SWUQueueChooseTarget($player, ['myBase-0', 'theirBase-0'], "Choose_a_base_to_heal", "HEAL_TARGET|5");
                return;
            case 'Defeat': {                              // defeat a unit with ≤3 remaining HP
                $targets = [];
                foreach (_SWUAllUnits() as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)
                        && (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) <= 3) $targets[] = $mz;
                }
                SWUQueueChooseTarget($player, array_values($targets), "Defeat_a_unit_with_3_or_less_remaining_HP", "DEFEAT_UNIT");
                return;
            }
            case 'Shield':                                // give a Shield to a unit
                SWUQueueChooseTarget($player, array_values(_SWUAllUnits()), "Give_a_Shield_to_a_unit", "GIVE_SHIELD");
                return;
        }
    }
    if ($cardID === 'SOR_107') {                          // Command
        switch ($label) {
            case 'Experience':                            // give 2 Experience tokens to a unit
                SWUQueueChooseTarget($player, array_values(_SWUAllUnits()), "Give_2_Experience_to_a_unit", "GIVE_EXPERIENCE|2");
                return;
            case 'PowerStrike': {                         // a friendly unit deals its power to a non-unique enemy
                $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
                SWUQueueChooseTarget($player, array_values($friendly), "Choose_the_friendly_unit", "SOR_107#0");
                return;
            }
            case 'Resource': {                            // put this event into play as a resource
                $mz = _SWUFindDiscardMzID($player, 'SOR_107');
                if ($mz !== null) SWURampResourceReady($player, $mz);
                return;
            }
            case 'Return':                                // return a unit from your discard to hand
                SWUQueueChooseTarget($player, array_values(ZoneSearch("myDiscard", AnyUnitFilter)),
                    "Return_a_unit_from_your_discard", "RETURN_DISCARD_UNIT");
                return;
        }
    }
    if ($cardID === 'SOR_155') {                          // Aggression
        switch ($label) {
            case 'Draw': DoDrawCard($player, 1); return;
            case 'DefeatUpgrades':                        // defeat up to 2 upgrades (possibly on
                                                          // DIFFERENT units — two chained "may defeat 1")
                SWUQueueDefeatUpgrade($player, "Defeat_an_upgrade_(1_of_2)", may:true, max:1, min:0,
                    thenHandler:'SOR_155_DEFEAT2');
                return;
            case 'Ready': {                               // ready a unit with ≤3 power
                $targets = [];
                foreach (_SWUAllUnits() as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) <= 3) $targets[] = $mz;
                }
                SWUQueueChooseTarget($player, array_values($targets), "Ready_a_unit_with_3_or_less_power", "READY_UNIT");
                return;
            }
            case 'Deal4':                                 // deal 4 to a unit
                SWUQueueChooseTarget($player, array_values(_SWUAllUnits()), "Deal_4_to_a_unit", "DEAL_UNIT_DAMAGE|4");
                return;
        }
    }
    if ($cardID === 'SOR_203') {                          // Cunning
        switch ($label) {
            case 'ReturnUnit': {                          // return a non-leader unit with ≤4 power to hand
                $targets = [];
                foreach (_SWUAllUnits() as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && !IsLeaderUnit($o) && intval(ObjectCurrentPower($o)) <= 4) $targets[] = $mz;
                }
                SWUQueueChooseTarget($player, array_values($targets), "Return_a_non-leader_unit_with_4_or_less_power", "BOUNCE_UNIT");
                return;
            }
            case 'BuffUnit':                              // give a unit +4/+0 this phase
                SWUQueueChooseTarget($player, array_values(_SWUAllUnits()), "Give_a_unit_+4/+0_this_phase", "APPLY_PHASE_BUFF|4|0|SOR_203");
                return;
            case 'Exhaust': {                             // exhaust up to 2 units
                $units = array_values(_SWUAllUnits());
                if (empty($units)) return;
                $max = min(2, count($units));
                DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode("&", $units), 1,
                    tooltip:"Exhaust_up_to_2_units");
                DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_203#0", 1);
                return;
            }
            case 'Discard':                               // an opponent discards a random card
                _SWUOpponentDiscardRandom($player);
                return;
        }
    }
}
// "$player's opponent discards one random card from hand" (SOR_203 mode; mirrors SOR_190).
function _SWUOpponentDiscardRandom(int $player): void {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer($player);
    $hand = &GetHand($opp);
    $liveIdx = [];
    foreach ($hand as $i => $c) { if (empty($c->removed)) $liveIdx[] = $i; }
    if (empty($liveIdx)) return;
    $pick = $liveIdx[array_rand($liveIdx)];
    $cid  = $hand[$pick]->CardID;
    $hand[$pick]->Remove();
    SWUAddToDiscard($opp, $cid, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', "P{$opp} discarded " . GameLogCardRef($cid) . ' at random');
}
// SOR_107 PowerStrike continuation: the chosen friendly unit deals its current power to a chosen
// non-unique enemy unit.
$customDQHandlers["SOR_107#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $dealer = GetZoneObject($lastDecision);
    if ($dealer === null || !empty($dealer->removed)) return;
    $power = intval(ObjectCurrentPower($dealer));
    $enemies = [];
    foreach (array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && !CardUnique($o->CardID)) $enemies[] = $mz;
    }
    if ($power <= 0 || empty($enemies)) return;
    SWUQueueChooseTarget($player, array_values($enemies), "Deal_power_to_a_non-unique_enemy_unit", "DEAL_UNIT_DAMAGE|{$power}");
};
// SOR_203 Exhaust continuation: exhaust each chosen unit (exhausting doesn't reindex).
$customDQHandlers["SOR_203#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        OnExhaustCard($player, $mz);
    }
};
// SOR_155 "defeat up to 2 upgrades" — the second link (fired by the first's thenHandler once it fully
// resolves). It re-reads the board, so this second upgrade can be on a DIFFERENT unit than the first.
$customDQHandlers["SOR_155_DEFEAT2"] = function($player, $parts, $lastDecision) {
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade_(2_of_2)", may:true, max:1, min:0);
};

// Universal: return the chosen discard-pile unit to its owner's hand.
$customDQHandlers["RETURN_DISCARD_UNIT"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};

// SOR_008 Hera Syndulla (deployed Leader Unit) — "On Attack: You may give an Experience token to
// another unique unit." (Her aspect-penalty-ignore passive lives in SWUAspectPenalty.) "Another" =
// exclude herself by UID; "unique unit" = any unit (friendly or enemy) with the unique flag.
$onAttackAbilities["SOR_008:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    $uid  = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = _SWUCollectUnits($uid, fn($o) => CardUnique($o->CardID));
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_an_Experience_to_another_unique_unit", "Choose_another_unique_unit", "GIVE_EXPERIENCE|1");
};
$customDQHandlers["MODAL_CHOOSE"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $cardID    = $parts[0] ?? '';
    $picksLeft = intval($parts[1] ?? 0);
    $block     = intval($parts[2] ?? 1);
    $labels    = (($parts[3] ?? '') !== '') ? explode(",", $parts[3]) : [];
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    _SWUModalResolveMode(intval($player), $cardID, $lastDecision);   // queues this mode's effects (block 1)
    if ($picksLeft - 1 > 0) {                                        // next picker at a higher block
        $remaining = array_values(array_filter($labels, fn($l) => $l !== $lastDecision));
        SWUQueueModalChoose(intval($player), $cardID, $remaining, $picksLeft - 1, $block + 1);
    }
};

// SOR_139 Force Choke — deal 5 to the chosen non-Vehicle unit, then THAT unit's controller draws.
// Custom (not DEAL_UNIT_DAMAGE) because the draw depends on the target's controller, captured BEFORE
// the damage in case the hit defeats the unit and cleans it up.
$customDQHandlers["SOR_139#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $controller = intval($o->Controller ?? 0);
    SWUDealDamageToUnit($lastDecision, 5, intval($player));
    if ($controller > 0) DoDrawCard($controller, 1);
};

// SOR_245 Medal Ceremony — give an Experience token to each chosen (up to 3) Rebel attacker.
$customDQHandlers["SOR_245#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === "" || $mz === "-" || $mz === "PASS") continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};

// Universal: defeat the unit at $lastDecision. Queued by PASSPARAMETER/MZCHOOSE +
// CUSTOM DEFEAT_UNIT (SOR_077 Takedown, SOR_078 Vanquish). No SWUAfterAction here —
// event flow handles cleanup via FINISH_PLAY_CARD.
$customDQHandlers["DEFEAT_UNIT"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
};

// Universal "an opponent chooses one of THEIR units to defeat" (SOR_040 Avenger, SOR_041 Power of the
// Dark Side). $parts[0] = '1' to restrict to non-leader units, '0' for any unit. Run via an
// intermediate CUSTOM (not inline from a trigger closure) so the cross-player $playerID survives —
// see SWUOpponentChoosesOwnUnit.
$customDQHandlers["OPP_DEFEAT_OWN_UNIT"] = function($player, $parts, $lastDecision) {
    $nonLeader = (($parts[0] ?? '1') === '1');
    $tip = $nonLeader ? 'Choose_a_non-leader_unit_to_defeat' : 'Choose_a_unit_to_defeat';
    SWUOpponentChoosesOwnUnit(intval($player), $nonLeader, $tip, 'DEFEAT_UNIT');
};

// SOR_040 Avenger — "When Played/On Attack: An opponent chooses a non-leader unit they control.
// Defeat that unit." Shared WhenPlayed + On Attack closure: queue the cross-player choose via the
// intermediate CUSTOM (nonLeader=1) so the opponent picks one of their own non-leader units.
$whenPlayedAbilities["SOR_040:0"] = $onAttackAbilities["SOR_040:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "OPP_DEFEAT_OWN_UNIT|1", 1);
};

// SOR_173 Bombing Run — deal 3 to each unit in the chosen arena (both players).
// YES = Ground, NO = Space. Dealing damage can defeat units and shift indices, so
// snapshot UniqueIDs first, then re-resolve the current mzID per UID before each hit.
$customDQHandlers["SOR_173#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $arena = ($lastDecision === "Space") ? "SpaceArena" : "GroundArena";
    $uids = [];
    foreach (array_merge(
        ZoneSearch("my$arena",    AnyUnitFilter),
        ZoneSearch("their$arena", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
    }
    foreach ($uids as $uid) {
        $found = null;
        foreach (array_merge(
            ZoneSearch("my$arena",    AnyUnitFilter),
            ZoneSearch("their$arena", AnyUnitFilter)
        ) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID) === $uid) { $found = $mz; break; }
        }
        if ($found !== null) SWUDealDamageToUnit($found, 3, intval($player));
    }
};

// SOR_127 Strike True — step 1: friendly dealer chosen ($lastDecision); collect
// enemy targets and carry the dealer mzID into step 2 via the handler param.
$customDQHandlers["SOR_127#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $friendlyMz = $lastDecision;
    $enemies = array_merge(
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Choose_an_enemy_unit", "SOR_127#1|" . $friendlyMz, 0);
};

// SOR_127 step 2: deal the dealer's current power to the chosen enemy ($lastDecision).
$customDQHandlers["SOR_127#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $fo = GetZoneObject($parts[0] ?? '');
    if ($fo === null || !empty($fo->removed)) return;
    $power = intval(ObjectCurrentPower($fo));
    if ($power > 0) SWUDealDamageToUnit($lastDecision, $power, intval($player));
};

// SOR_220 Surprise Strike — give the chosen attacker +3/+0 for this attack, then attack.
$customDQHandlers["SOR_220#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if ($attacker === null || !empty($attacker->removed)) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }
    SWUAddAttackPowerBonus($attackerMzID, 3);  // +3/+0 for THIS attack (one-shot, not a phase buff)
    BeginSWUAttack($player, $attackerMzID);   // handles exhaust + target selection + combat continuation
    $playerID = $savedPID;
};

// SOR_234 Maximum Firepower — step 1: first Imperial ($lastDecision) chosen; pick the target.
$customDQHandlers["SOR_234#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $imp1Mz = $lastDecision;
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_the_target_unit", "SOR_234#1|" . $imp1Mz, 0);
};

// Step 2: imp1 deals its power to the target ($lastDecision); then pick a SECOND Imperial.
$customDQHandlers["SOR_234#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $imp1Mz   = $parts[0] ?? '';
    $imp1     = GetZoneObject($imp1Mz);
    $target   = GetZoneObject($lastDecision);
    if ($imp1 === null || !empty($imp1->removed) || $target === null || !empty($target->removed)) return;
    $imp1UID   = intval($imp1->UniqueID ?? -1);
    $targetUID = intval($target->UniqueID ?? -1);
    SWUDealDamageToUnit($lastDecision, intval(ObjectCurrentPower($imp1)), intval($player));
    // Another friendly Imperial (≠ imp1, re-resolved after possible index shifts).
    $imp2 = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? -2) === $imp1UID) continue;        // exclude the first Imperial
        if (HasTrait($o->CardID, 'Imperial')) $imp2[] = $mz;
    }
    if (empty($imp2)) return;                                          // no second Imperial → done
    SWUQueueChooseTarget(intval($player), $imp2, "Choose_another_Imperial_unit", "SOR_234#2|" . $targetUID, 0);
};

// Step 3: the second Imperial ($lastDecision) deals its power to the same target (by UID).
$customDQHandlers["SOR_234#2"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $imp2 = GetZoneObject($lastDecision);
    if ($imp2 === null || !empty($imp2->removed)) return;
    $targetMz = SWUFindMzByUID(intval($parts[0] ?? -1));              // same unit — may already be defeated
    if ($targetMz === null) return;
    SWUDealDamageToUnit($targetMz, intval(ObjectCurrentPower($imp2)), intval($player));
};

// SOR_252 Restock — move the chosen discard cards to the bottom of their owner's deck (random order).
$customDQHandlers["SOR_252#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $byOwner = [1 => [], 2 => []];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        $owner = (strpos($mz, 'my') === 0) ? intval($player) : GetOpponent(intval($player));
        $byOwner[$owner][] = $o->CardID;
        $o->removed = true;
    }
    DecisionQueueController::CleanupRemovedCards();
    foreach ($byOwner as $owner => $ids) {
        if (!empty($ids)) _topDeckPutRemainingToBottom($owner, $ids);   // shuffles → bottom of deck
    }
};

// SOR_033 Death Trooper — When Played: deal 2 to a friendly ground unit AND 2 to an enemy ground unit.
$whenPlayedAbilities["SOR_033:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $friendly = ZoneSearch("myGroundArena", AnyUnitFilter);
    if (!empty($friendly)) {
        SWUQueueChooseTarget(intval($player), $friendly, "Deal_2_to_a_friendly_ground_unit", "DEAL_UNIT_DAMAGE|2");
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_033#0", 1);
};
$customDQHandlers["SOR_033#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        "Deal_2_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|2", 0);
};

// SOR_038 Count Dooku — When Played: you may defeat a unit with 4 or less remaining HP.
$whenPlayedAbilities["SOR_038:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 4) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Defeat_a_unit_with_4_or_less_remaining_HP?", "Defeat_a_unit_with_4_or_less_remaining_HP", "DEFEAT_UNIT");
};

// SOR_090 Devastator — When Played: you may deal damage to a unit equal to resources you control.
$whenPlayedAbilities["SOR_090:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $n = SWUResourceCount(intval($player));
    if ($n <= 0) return;
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_damage_equal_to_resources_you_control?", "Deal_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $n);
};

// SOR_132 Imperial Interceptor — When Played: you may deal 3 to a space unit.
$whenPlayedAbilities["SOR_132:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("mySpaceArena",    AnyUnitFilter),
        ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_3_damage_to_a_space_unit?", "Deal_3_to_a_space_unit", "DEAL_UNIT_DAMAGE|3");
};

// SOR_135 Emperor Palpatine (Unit) — Overwhelm (auto-wired) + When Played: deal 6 damage divided
// as you choose among enemy units. MZSPLITASSIGN over both enemy arenas; the full 6 must be
// assigned (UI-gated). SWUDealSplitDamage applies it SIMULTANEOUSLY (apply-all then defeat sweep).
$whenPlayedAbilities["SOR_135:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) return;   // no enemy units → fizzle
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "6|" . implode("&", $targets), 1, tooltip:"Divide_6_damage_among_enemy_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SPLIT_DAMAGE", 1);
};

// Universal: deal the MZSPLITASSIGN result ($lastDecision) simultaneously (apply-all then sweep).
// Shared by any "deal N divided among units" card (SOR_135 Palpatine, SOR_092 Overwhelming Barrage).
$customDQHandlers["SPLIT_DAMAGE"] = function($player, $parts, $lastDecision) {
    SWUDealSplitDamage(intval($player), (string)$lastDecision);
};

// SOR_092 Overwhelming Barrage — the chosen friendly dealer ($lastDecision) gets +2/+2 for this
// phase, then deals damage equal to its BUFFED power split among any number of OTHER units.
$customDQHandlers["SOR_092#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    SWUApplyPhaseBuff($lastDecision, 2, 2, "SOR_092");          // +2/+2 for this phase (buff stands even if no targets)
    $dealer = GetZoneObject($lastDecision);
    if ($dealer === null || !empty($dealer->removed)) return;
    $dealerUID = intval($dealer->UniqueID ?? 0);
    $power = ObjectCurrentPower($dealer);                       // power AFTER the buff
    if ($power <= 0) return;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? -1) === $dealerUID) continue; // "other units" — exclude the dealer
        $targets[] = $mz;
    }
    if (empty($targets)) return;                               // no other units → buff applied, no damage
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", $power . "|" . implode("&", $targets), 1, tooltip:"Divide_damage_among_other_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SPLIT_DAMAGE", 1);
};

// SOR_052 Redemption (Unit, Space, 6/9) — Sentinel (auto) + When Played: heal up to 8 total damage
// from any number of units and/or bases, then deal that much (the ACTUAL healed) to itself. Uses the
// MZSPLITASSIGN "up to" mode (per-target cap = current damage; partial submit allowed).
$whenPlayedAbilities["SOR_052:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = $self ? intval($self->UniqueID ?? 0) : 0;
    $specs = [];
    // Damaged units in any arena (cap each at its current damage — can't heal more than is there).
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        $dmg = intval($o->Damage ?? 0);
        if ($dmg > 0) $specs[] = "{$mz}:{$dmg}";
    }
    // Damaged bases.
    foreach (['myBase-0' => intval($player), 'theirBase-0' => GetOpponent(intval($player))] as $baseMz => $bp) {
        $base = GetBase($bp);
        $bdmg = (count($base) > 0 && empty($base[0]->removed)) ? intval($base[0]->Damage ?? 0) : 0;
        if ($bdmg > 0) $specs[] = "{$baseMz}:{$bdmg}";
    }
    if (empty($specs)) return; // nothing damaged → no heal, no self-damage
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "8|" . implode("&", $specs) . "|UPTO", 1, tooltip:"Heal_up_to_8_damage_(units_and-or_bases)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_052#0|{$selfUID}", 1);
};

// Heal each assigned target (clamped by OnHealUnit/OnHealBase, which also fire the heal animation),
// sum the ACTUAL healed, then deal that to Redemption ($parts[0] = its UniqueID).
$customDQHandlers["SOR_052#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $selfUID  = intval($parts[0] ?? 0);
    $totalHealed = 0;
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        foreach (explode(',', (string)$lastDecision) as $pair) {
            $p = explode(':', $pair);
            if (count($p) < 2) continue;
            $mz = trim($p[0]); $amt = intval($p[1]);
            if ($amt <= 0) continue;
            if (strpos($mz, 'Base') !== false) {
                $tp = (strpos($mz, 'my') === 0) ? intval($player) : GetOpponent(intval($player));
                $base = GetBase($tp);
                $before = (count($base) > 0) ? intval($base[0]->Damage ?? 0) : 0;
                OnHealBase(intval($player), $tp, $amt);
                $base = GetBase($tp);
                $after = (count($base) > 0) ? intval($base[0]->Damage ?? 0) : 0;
                $totalHealed += max(0, $before - $after);
            } else {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                $before = intval($o->Damage ?? 0);
                OnHealUnit(intval($player), $mz, $amt);
                $totalHealed += max(0, $before - intval($o->Damage ?? 0)); // $o is a live handle
            }
        }
    }
    if ($totalHealed > 0) {
        $selfMz = SWUFindMzByUID($selfUID);
        if ($selfMz !== null) SWUDealDamageToUnit($selfMz, $totalHealed, intval($player));
    }
};

// SOR_134 Ruthless Raider — When Played / When Defeated: deal 2 to an enemy base AND 2 to an enemy unit.
$sor134RuthlessRaider = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToBase(2, GetOpponent(intval($player)));
    $enemy = array_merge(
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Deal_2_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|2");
};
$whenPlayedAbilities["SOR_134:0"]    = $sor134RuthlessRaider;
$whenDefeatedAbilities["SOR_134:0"]  = $sor134RuthlessRaider;

// SOR_176 ISB Agent — When Played: you may reveal an event from your hand. If you do, deal 1 to a unit.
// Single MZMAYCHOOSE; gated on having an event to reveal. The reveal is the commitment, so it
// happens in the SOR_176 handler only when the player actually picks a target.
$whenPlayedAbilities["SOR_176:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (empty(ZoneSearch("myHand", ["Event"]))) return;   // nothing to reveal → ability does nothing
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Reveal_an_event_from_your_hand_to_deal_1_damage?", "Deal_1_damage_to_a_unit", "SOR_176#0");
};
$customDQHandlers["SOR_176#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return; // declined → no reveal, no damage
    global $playerID;
    $playerID = intval($player);
    $events = ZoneSearch("myHand", ["Event"]);
    if (!empty($events)) DoRevealCard(intval($player), $events[0]);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
};

// SOR_121 Hardpoint Heavy Blaster (upgrade) — granted On Attack: if not attacking a base,
// you may deal 2 to a unit in the defender's arena. $mzID = host unit; defender via SWU var.
$onAttackAbilities["SOR_121:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    if (strpos($defenderMz, 'Arena') === false) return;   // attacking a base → no effect
    $arena = (strpos($defenderMz, 'Ground') !== false) ? 'GroundArena' : 'SpaceArena';
    $targets = array_merge(
        ZoneSearch("my{$arena}",    AnyUnitFilter),
        ZoneSearch("their{$arena}", AnyUnitFilter)
    );
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_2_to_a_unit_in_the_defenders_arena?", "Deal_2_to_a_unit_in_the_arena", "DEAL_UNIT_DAMAGE|2");
};

// SOR_151 Karabast — step 1: friendly dealer chosen; pick the enemy target.
$customDQHandlers["SOR_151#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $friendlyMz = $lastDecision;
    $enemy = array_merge(
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($enemy)) return;
    if (count($enemy) === 1) DecisionQueueController::AddDecision($player, "PASSPARAMETER", $enemy[0], 0);
    else DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $enemy), 0, "Choose_an_enemy_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_151#1|" . $friendlyMz, 0);
};
// Step 2: deal (friendly's damage + 1) to the chosen enemy.
$customDQHandlers["SOR_151#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $fo = GetZoneObject($parts[0] ?? '');
    if ($fo === null || !empty($fo->removed)) return;
    $amount = intval($fo->Damage ?? 0) + 1;
    SWUDealDamageToUnit($lastDecision, $amount, intval($player));
};

// ── Unit activated abilities ("Action [...]") ───────────────────────────────
// $unitAbilities[providerCardID] = function($player, $hostMzID). The provider is the
// unit's own CardID, or an attached upgrade's CardID (SWUGetUnitActionProvider).
// $unitActionResourceCosts[providerCardID] = resource cost (omit = 0). SWUUnitAction
// pays the Exhaust cost and dispatches; the handler ends with SWU_AFTER_ACTION.
$unitAbilities = [];
$unitActionResourceCosts = [];
// Base cost-kind per provider: 'exhaust' (default — requires ready, exhausts the unit) or
// 'defeat' (no ready requirement; the unit is defeated to pay, e.g. SOR_110 Frontline Shuttle).
$unitActionCostKind = [];
$unitActionCostKind["SOR_110"] = 'defeat';
// SOR_184 Fett's Firespray — "Action [2 resources]:" with NO exhaust: the unit isn't tapped and
// needn't be ready, so the action is repeatable while resources last.
$unitActionCostKind["SOR_184"] = 'none';
$unitActionResourceCosts["SOR_184"] = 2;

// SOR_184 Fett's Firespray — Action [2 resources]: Exhaust a non-unique unit (either player's).
$unitAbilities["SOR_184"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = _SWUNonUniqueUnitTargets(intval($player));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_non-unique_unit", "EXHAUST_UNIT");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// SOR_184 Fett's Firespray — When Played: if you control Boba Fett or Jango Fett (leader, unit, or
// upgrade), ready this unit (so it can act the turn it's played).
$whenPlayedAbilities["SOR_184:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!_SWUControlsTitle(intval($player), ['Boba Fett', 'Jango Fett'])) return;
    OnReadyCard(intval($player), $mzID);
};

// JTL_134 General Hux — Action [Exhaust]: If you played a First Order card this phase, draw a card.
// (SWU_PLAYED_FO is set in ActivateCard when a First Order card is played; cleared at RegroupPhaseStart.)
$unitAbilities["JTL_134"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_FO') > 0) DoDrawCard(intval($player), 1);
    SWUAfterAction($player);
};

// JTL_146 Massassi Tactical Officer — Action [Exhaust]: Attack with a Fighter unit (+2/+0 this attack).
$unitAbilities["JTL_146"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $fighters = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
            if (HasTrait($u->CardID, 'Fighter')) $fighters[] = "{$zone}-{$i}";
        }
    }
    if (empty($fighters)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $fighters, "Choose_a_Fighter_to_attack_with_(+2/+0)", "JTL_146#0");
};
$customDQHandlers["JTL_146#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') { SWUAfterAction($player); return; }
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) { SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);   // combat owns SWUAfterAction
};

// JTL_186 Mist Hunter — On Attack: If you played a Bounty Hunter or Pilot card this phase, may draw a card.
$onAttackAbilities["JTL_186:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_BOUNTYHUNTER') <= 0
        && GlobalEffectCount(intval($player), 'SWU_PLAYED_PILOT') <= 0) return;
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Draw_a_card?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_186#0', 1);
};
$customDQHandlers["JTL_186#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    DoDrawCard(intval($player), 1);
};

// JTL_187 Bossk — On Attack: Exhaust the defender and deal 1 damage to it (if it's a unit). Reads the
// current-defender SWUVar (also fires when granted to a host via the Piloting "Attached unit gains").
$onAttackAbilities["JTL_187:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defMz = GetSWUVar('SWU_CURRENT_DEFENDER', '');
    if ($defMz === '' || strpos($defMz, 'Base') !== false) return;   // defender must be a unit
    $def = GetZoneObject($defMz);
    if ($def === null || !empty($def->removed)) return;
    $def->Status = 0;                                  // exhaust the defender
    SWUDealDamageToUnit($defMz, 1, intval($player));   // deal 1 damage to it
};

// JTL_238 Sith Trooper — On Attack: +1/+0 for this attack for each damaged unit the defending player
// (the opponent, in 2-player) controls.
$onAttackAbilities["JTL_238:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $count = 0;
    foreach (array_merge(GetGroundArena($opp), GetSpaceArena($opp)) as $u) {
        if (empty($u->removed) && intval($u->Damage) > 0) $count++;
    }
    if ($count > 0) SWUAddAttackPowerBonus($mzID, $count);
};

// JTL_147 Black One — On Attack: If you control Poe Dameron (unit, upgrade, or leader), may deal 1 to a unit.
$onAttackAbilities["JTL_147:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!_SWUControlsTitle(intval($player), ['Poe Dameron'])) return;
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Deal_1_damage_to_a_unit", "Choose_a_unit", "DEAL_UNIT_DAMAGE|1");
};

// SOR_094 Bail Organa — Action [Exhaust]: give an Experience token to another friendly unit.
$unitAbilities["SOR_094"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUOtherFriendlyUnits(intval($player), $mzID);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_another_friendly_unit", "GIVE_EXPERIENCE|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// SOR_093 Alliance Dispatcher (own action) / TWI_120 Strategic Acumen (upgrade-granted) —
// Action [Exhaust]: Play a unit from your hand. It costs 1 resource less.
$unitAbilities["SOR_093"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 1);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_from_your_hand_(it_costs_1_less)", "DISCOUNT_PLAY_FROM_HAND|1");
};
$unitAbilities["TWI_120"] = $unitAbilities["SOR_093"]; // same effect, granted by the upgrade

// SOR_129 Admiral Ozzel — Action [Exhaust]: Play an Imperial unit from your hand (paying its cost).
// It enters play ready. Each opponent may ready a unit.
$unitAbilities["SOR_129"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && HasTrait($o->CardID ?? '', 'Imperial')) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Play_an_Imperial_unit_from_your_hand_(it_enters_ready)", "OZZEL_PLAY");
};

// Plays the chosen Imperial ($lastDecision) at full cost, forcing it to enter READY, then lets each
// opponent may-ready a unit. ActivateCard owns the play's end-of-action (do not add SWU_AFTER_ACTION).
$customDQHandlers["OZZEL_PLAY"] = function($player, $parts, $lastDecision) {
    global $playerID, $gForceEnterReady;
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    $playerID = intval($player);
    // Each opponent may ready a unit — queue FIRST so it resolves before the play's after-action
    // swaps the turn. Targets are the opponent's units from THEIR perspective (myArena-N).
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;
    $oppUnits = array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    );
    if (!empty($oppUnits)) {
        SWUQueueMayChooseTarget($opp, $oppUnits, "You_may_ready_a_unit", "Ready_a_unit", "READY_UNIT");
    }
    // Play the chosen Imperial — it enters READY (Ozzel overrides the default exhausted entry).
    $playerID = intval($player);
    $gForceEnterReady = true;
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gForceEnterReady = false;
};

// SOR_003 Chewbacca — plays the chosen ≤3 unit ($lastDecision) at full cost, granting it Sentinel
// for this phase via the SOR_003 turn-effect token (applied to the entering unit by ActivateCard's
// $gPlayGrantTurnEffect hook). ActivateCard owns the play's end-of-action — do not add SWU_AFTER_ACTION.
$customDQHandlers["SOR_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gPlayGrantTurnEffect;
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    $playerID = intval($player);
    $gPlayGrantTurnEffect = 'SOR_003'; // Sentinel for this phase (registry-driven)
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gPlayGrantTurnEffect = null;
};

// SOR_219 Sneak Attack — plays the chosen hand unit ($lastDecision) at a 3-resource discount, forcing
// it to enter READY ($gForceEnterReady) and tagging it SWU_SNEAK_DEFEAT (RegroupPhaseStart defeats it).
// This runs inside the EVENT's resolution, so the event's FINISH_PLAY_CARD owns the after-action: the
// inner ActivateCard's own turn advance is neutralised by capturing/restoring the turn state (mirrors
// SWUPlayTopDeckCard). Mandatory single-target choose, so a '-'/empty answer means no playable unit.
$customDQHandlers["SOR_219#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID, $gForceEnterReady, $gPlayGrantTurnEffect, $gTurnPlayer;
    $playerID = intval($player);
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    $gForceEnterReady     = true;
    $gPlayGrantTurnEffect = 'SWU_SNEAK_DEFEAT';
    ActivateCard(intval($player), $lastDecision, false, 3);
    $gForceEnterReady     = false;
    $gPlayGrantTurnEffect = null;
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// TWI_005 Count Dooku (deployed Leader Unit) — On Attack: the next Separatist card
// you play this phase gains Exploit 3 (additive with any printed Exploit).
// Arms a one-shot lingering flag consumed in SWUBeginPlayCard; cleared at RegroupPhaseStart.
$onAttackAbilities["TWI_005:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_DOOKU_NEXT_SEPARATIST_EXPLOIT');
};

// Resolves Count Dooku LEADER side: "Play a Separatist card from your hand. It gains Exploit 1."
// Sets $gPlayGrantedExploit = 1 and delegates to SWUBeginPlayCard (which immediately resets
// the global after capturing it, preventing any grant leak). ActivateCard / SWUBeginPlayCard
// own the end-of-action — do NOT append SWU_AFTER_ACTION here.
$customDQHandlers["TWI_005#0"] = function($player, $parts, $lastDecision) {
    global $gPlayGrantedExploit, $playerID;
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    $playerID = intval($player);
    $gPlayGrantedExploit = 1; // grant Exploit 1 to the chosen Separatist card
    SWUBeginPlayCard(intval($player), $lastDecision);
};

// SOR_177 Bib Fortuna — Action [Exhaust]: Play an event from your hand. It costs 1 less.
$unitAbilities["SOR_177"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUHandPlayablesAtDiscount(intval($player), ['Event'], 1);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Play_an_event_from_your_hand_(it_costs_1_less)", "DISCOUNT_PLAY_FROM_HAND|1");
};

// Cross-card: play the chosen hand card ($lastDecision) at $parts[0] discount. ActivateCard
// owns the end-of-action (unit branch → SWUAfterAction; event branch → FINISH_PLAY_CARD),
// so do NOT append SWU_AFTER_ACTION here — that would resolve the action twice.
$customDQHandlers["DISCOUNT_PLAY_FROM_HAND"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    $discount = max(0, intval($parts[0] ?? 1));
    ActivateCard(intval($player), $lastDecision, false, $discount);
};

// SHD_028 Doctor Pershing — Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card.
// The Exhaust is paid by SWUUnitAction; this closure pays the additional cost (deal 1 to a
// friendly unit — Pershing himself is always a valid target) then draws. Cost before effect.
$unitAbilities["SHD_028"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    );
    if (empty($targets)) { DoDrawCard(intval($player), 1); SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_friendly_unit", "DEAL_UNIT_DAMAGE|1");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DRAW_CARD|1", 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// Universal: draw $parts[0] cards for the acting player.
$customDQHandlers["DRAW_CARD"] = function($player, $parts, $lastDecision) {
    DoDrawCard(intval($player), max(1, intval($parts[0] ?? 1)));
};

// SOR_110 Frontline Shuttle — Action [defeat this unit]: Attack with a unit, even if it's
// exhausted. It can't attack bases for this attack. SWUUnitAction already defeated the
// Shuttle (the 'defeat' cost); pick a remaining friendly unit and attack with no-bases.
$unitAbilities["SOR_110"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $attackers = SWUUnitsWithNonBaseAttackTarget(intval($player)); // Shuttle already gone
    if (empty($attackers)) { SWUAfterAction($player); return; }
    if (count($attackers) === 1) { BeginSWUAttack(intval($player), $attackers[0], noBases: true); return; }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $attackers), 1, "Attack_with_a_unit_(it_can't_attack_bases)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_110#0", 1);
};
// BeginSWUAttack (combat) owns the after-action — do NOT append SWU_AFTER_ACTION.
$customDQHandlers["SOR_110#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    BeginSWUAttack(intval($player), $lastDecision, noBases: true);
};

// ── JTL_013 Poe Dameron (deployed as pilot) — deployBox Hop ─────────────────
// "Action [1 resource]: Attach this upgrade to a friendly Vehicle unit without a Pilot on it.
// Use this ability only once each round."
// When JTL_013 is attached as a pilot subcard, the host gains this action. The host exhausts
// if SWUUnitAction's default 'exhaust' cost-kind fires — but this hop is NOT exhaust-keyed
// (card text is "[1 resource]" only, no Exhaust). Set costKind='none' so the host doesn't
// exhaust; only the 1 resource is spent.
// Hop mechanics: splice JTL_013 out of the current host's Subcards without defeating it,
// then re-attach to the new Vehicle via _SWUFinalizeUpgradeAttach (ignoreCost=true, isPilot=true).
// Once-per-round: gated by the Poe leader's NumUses budget (refreshed by SWUResetAllNumUses each round).
// JTL_050 Phantom II — "Action [1 resource]: If this card is a unit, attach it as an upgrade to The Ghost.
// (It's no longer a unit. Defeat all upgrades on it and remove all damage from it.)" costKind 'none'
// (resource-only, no exhaust). Reuses SWUMoveUnitToUpgrade with the named host JTL_053; the +3/+3 + Grit
// grant lives in ObjectCurrentPower/HP + the Grit conditional. Affordability gated on The Ghost in play.
$unitActionCostKind["JTL_050"] = 'none';
$unitActionResourceCosts["JTL_050"] = 1;
$unitAbilities["JTL_050"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $ghostMz = null;
    foreach (['mySpaceArena', 'myGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && ($o->CardID ?? '') === 'JTL_053') { $ghostMz = $mz; break 2; }
        }
    }
    if ($ghostMz !== null) {
        SWUMoveUnitToUpgrade($mzID, $ghostMz, false); // Phantom II is a special upgrade, not a Pilot
    }
    SWUAfterAction($player);
};

$unitActionCostKind["JTL_013"] = 'none';
$unitActionResourceCosts["JTL_013"] = 1;

$unitAbilities["JTL_013"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);

    // Once-per-round guard.
    if (!SWUHasUseAvailable(SWUGetLeader(intval($player)))) {
        SWUAfterAction($player);
        return;
    }

    // Collect eligible hop targets: friendly Vehicles with 0 pilots, excluding the current host.
    $all = array_merge(
        ZoneSearch("myGroundArena", ["Unit", "Leader Unit"]),
        ZoneSearch("mySpaceArena",  ["Unit", "Leader Unit"])
    );
    $targets = array_values(array_filter($all, function($mz) use ($mzID) {
        if ($mz === $mzID) return false; // exclude the current host
        $hostObj = GetZoneObject($mz);
        if ($hostObj === null || !empty($hostObj->removed)) return false;
        if (!HasTrait($hostObj->CardID ?? '', 'Vehicle')) return false;
        return SWUVehiclePilotCount($hostObj) === 0;
    }));

    if (empty($targets)) {
        SWUAfterAction($player);
        return;
    }

    // Splice JTL_013 out of the current host's Subcards (no defeat — it moves, not dies).
    $currentHost = GetZoneObject($mzID);
    if ($currentHost !== null && is_array($currentHost->Subcards ?? null)) {
        foreach ($currentHost->Subcards as $key => $sub) {
            $subCardID = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            $isRemoved = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
            if (!$isRemoved && $subCardID === 'JTL_013') {
                array_splice($currentHost->Subcards, $key, 1);
                break;
            }
        }
    }

    // Mark once-per-round used BEFORE queuing the attach (so it's set even if we auto-attach).
    SWUConsumeUse(SWUGetLeader(intval($player))); // once/round hop via leader NumUses

    if (count($targets) === 1) {
        // Auto-attach to the single eligible Vehicle — route through the chokepoint.
        _SWUFinalizeUpgradeAttach(intval($player), 'JTL_013', '', $targets[0], 0, true, true);
        return;
    }

    // 2+ vehicles: let the player pick. Store the decision, then finalize.
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
        tooltip: 'Choose_a_Vehicle_to_hop_Poe_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_013#1', 1);
};

// JTL_013#1 (was POE_013_HOP) — receives the MZCHOOSE host mzID, finalizes the hop attach.
$customDQHandlers["JTL_013#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hostMz = $lastDecision ?? '';
    if ($hostMz === '' || $hostMz === '-') {
        SWUAfterAction(intval($player));
        return;
    }
    _SWUFinalizeUpgradeAttach(intval($player), 'JTL_013', '', $hostMz, 0, true, true);
};

// ── Batch 4.4: exhaust / ready / bounce ─────────────────────────────────────

// SOR_039 AT-AT Suppressor — When Played: Exhaust all ground units (both players).
$whenPlayedAbilities["SOR_039:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    foreach (array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        OnExhaustCard($player, $mz);
    }
};

// SOR_086 Gladiator Star Destroyer — When Played: Give a unit Sentinel for this phase.
$whenPlayedAbilities["SOR_086:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), _SWUCollectUnits(-1, fn($o) => true), // any unit, either player
        'Give_a_unit_Sentinel_for_this_phase', 'GRANT_PHASE_KEYWORD|SOR_086');
};
// Universal: tag the chosen unit ($lastDecision) with grant token $parts[0] — a source CardID
// (e.g. "SOR_086") that the registry resolves to its granted keyword + duration, so the Active
// Effects UI shows provenance. HasKeyword_* reads it via SWUHasTurnEffectKeyword; expiry is driven
// by the registry duration (SWUExpireTurnEffects). The token is added verbatim (no uppercasing —
// a CardID is already canonical).
$customDQHandlers["GRANT_PHASE_KEYWORD"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, (string)($parts[0] ?? ''));
};

// SOR_099 Bright Hope — When Played: You may return a friendly non-leader GROUND unit to
// hand. If you do, draw a card.
$whenPlayedAbilities["SOR_099:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player),
        ZoneSearch('myGroundArena', NonLeaderUnitFilter), // non-leader ground
        'Return_a_friendly_ground_unit_to_hand_(then_draw)?', 'Choose_a_friendly_ground_unit_to_return', 'SOR_099#0');
};
$customDQHandlers["SOR_099#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    if (SWUBounceUnit($player, $lastDecision)) DoDrawCard(intval($player), 1); // "If you do, draw"
};

// SOR_178 Cartel Spacer — When Played: If you control another [Cunning] unit, exhaust an
// enemy unit that costs 4 or less. Automatic.
$whenPlayedAbilities["SOR_178:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = $self ? intval($self->UniqueID ?? -1) : -1;
    $hasCunning = false;
    foreach (array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? -2) === $selfUID) continue;                  // "another"
        if (strpos(CardAspect($o->CardID) ?? '', 'Cunning') !== false) { $hasCunning = true; break; }
    }
    if (!$hasCunning) return;
    $targets = [];
    foreach (array_merge(
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval(CardCost($o->CardID) ?? 99) <= 4) $targets[] = $mz;
    }
    SWUQueueChooseTarget(intval($player), $targets, 'Exhaust_an_enemy_unit_(cost_4_or_less)', 'EXHAUST_UNIT');
};

// SOR_202 Cantina Bouncer — When Played: You may return a non-leader unit to hand (either player).
$whenPlayedAbilities["SOR_202:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits(-1, fn($o) => !IsLeaderUnit($o)),
        'Return_a_non-leader_unit_to_hand?', 'Choose_a_non-leader_unit_to_return', 'BOUNCE_UNIT');
};

// SOR_208 Outer Rim Headhunter — On Attack: If you control a leader unit, you may exhaust a
// non-leader unit. (Raid 1 is an auto keyword.)
$onAttackAbilities["SOR_208:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits(-1, fn($o) => !IsLeaderUnit($o)),
        'Exhaust_a_non-leader_unit?', 'Choose_a_non-leader_unit_to_exhaust', 'EXHAUST_UNIT');
};

// SOR_209 Pirated Starfighter — When Played: Return a friendly non-leader unit to hand
// (mandatory). (Raid 1 is an auto keyword.)
$whenPlayedAbilities["SOR_209:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), array_merge(
        ZoneSearch('myGroundArena', NonLeaderUnitFilter),
        ZoneSearch('mySpaceArena',  NonLeaderUnitFilter)
    ), 'Return_a_friendly_non-leader_unit_to_hand', 'BOUNCE_UNIT');
};

// SOR_214 Smuggling Compartment — Upgrade grants the host: "On Attack: Ready a resource."
// Auto-readies the first exhausted resource (mirrors SOR_189).
$onAttackAbilities["SOR_214:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $resources = GetResources($player);
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        if (intval($resources[$i]->Status) === 0) {
            DecisionQueueController::AddDecision($player, 'PASSPARAMETER', "myResources-{$i}", 1);
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'READY_RESOURCE', 1);
            return;
        }
    }
};

// SOR_221 Outmaneuver (event) — receives the OPTIONCHOOSE arena pick; exhausts every unit in
// the chosen arena (both players). Queued by OnPlayEvent (CardEffects.php).
$customDQHandlers["SOR_221#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    foreach (array_merge(
        ZoneSearch("my{$arena}",    AnyUnitFilter),
        ZoneSearch("their{$arena}", AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        OnExhaustCard($player, $mz);
    }
};

// ── Batch 4.5: attack-with riders + OnAttack utility ────────────────────────

// Ready friendly units (Status=1) across both arenas — candidate attackers. Caller sets $playerID.
function _SWUReadyFriendlyUnits(int $player): array {
    global $playerID;
    $playerID = intval($player);
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if ($u === null || !empty($u->removed)) continue;
            if (intval($u->Status) === 1) $out[] = "{$zone}-{$i}";
        }
    }
    return $out;
}

// SOR_206 Mining Guild TIE Fighter — On Attack: You may pay 2 resources. If you do, draw a card.
$onAttackAbilities["SOR_206:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 2) return; // can't pay → not offered
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, 'Pay_2_resources_to_draw_a_card?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_206#0', 1);
};
$customDQHandlers["SOR_206#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID;
    $playerID = intval($player);
    if (!SWUExhaustResources($player, 2)) return; // pay the cost; fizzle if somehow unaffordable
    DoDrawCard(intval($player), 1);
};

// SOR_227 Snowtrooper Lieutenant (Imperial) / SOR_240 Fleet Lieutenant (Rebel) — When Played:
// You may attack with a unit; if it's a {trait} unit it gets +2/+0 for THIS attack.
// Single MZMAYCHOOSE over ready friendly units; ATTACK_WITH_TRAIT_BUFF resolves the pick
// (and already treats a '-' decline as a null attacker → CleanupRemovedCards, so declining
// ends the action cleanly via SWU_TRIGGER_RESUME).
$swuAttackWithTraitWhenPlayed = function($trait) {
    return function($player, $mzID) use ($trait) {
        global $playerID;
        $playerID = intval($player);
        SWUQueueMayChooseTarget(intval($player), _SWUReadyFriendlyUnits(intval($player)),
            'Attack_with_a_unit?', 'Choose_a_unit_to_attack_with', "ATTACK_WITH_TRAIT_BUFF|{$trait}");
    };
};
$whenPlayedAbilities["SOR_227:0"] = $swuAttackWithTraitWhenPlayed('Imperial');
$whenPlayedAbilities["SOR_240:0"] = $swuAttackWithTraitWhenPlayed('Rebel');

// Buff the chosen attacker +2/+0 if it has $parts[0] trait, then attack. Attack-during-WhenPlayed:
// BeginSWUAttack's combat skips SWUAfterAction in trigger-resume mode; SWU_TRIGGER_RESUME ends it.
$customDQHandlers["ATTACK_WITH_TRAIT_BUFF"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $trait        = $parts[0] ?? '';
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if ($attacker === null || !empty($attacker->removed) || intval($attacker->Status) !== 1) {
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return;
    }
    if (HasTrait($attacker->CardID, $trait)) SWUAddAttackPowerBonus($attackerMzID, 2);
    BeginSWUAttack($player, $attackerMzID);
    $playerID = $savedPID;
};

// ── Batch 4.6: draw / resource / discard / conditional-upgrade ───────────────

// Find ANY non-removed discard entry of $cardID (raw mzID "myDiscard-N"). Caller sets $playerID.
// "Any copy" suffices for ramp effects: simultaneous defeats each fire one handler, and MZMove
// removes the moved copy so the next handler finds another. Returns null if none present.
function _SWUFindDiscardMzID(int $player, string $cardID): ?string {
    $discard = GetDiscard($player);
    for ($i = 0; $i < count($discard); $i++) {
        if (!empty($discard[$i]->removed)) continue;
        if (($discard[$i]->CardID ?? '') === $cardID) return "myDiscard-{$i}";
    }
    return null;
}

// SOR_083 Superlaser Technician — When Defeated: put this unit into play as a (ready) resource.
// Auto-resolves (nobody declines a ramp in practice). The unit is already in discard on defeat,
// so move a SOR_083 copy from there to the resource zone.
$whenDefeatedAbilities["SOR_083:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $mz = _SWUFindDiscardMzID(intval($player), 'SOR_083');
    if ($mz !== null) SWURampResourceReady(intval($player), $mz);
};

// SOR_136 Vader's Lightsaber — When Played (as upgrade): If attached unit is Darth Vader, you
// may deal 4 damage to a ground unit. $mzID = host unit mzID (WhenPlayed fallback, like SOR_053).
$whenPlayedAbilities["SOR_136:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    if (CardTitle($host->CardID) !== 'Darth Vader') return;
    SWUQueueMayChooseTarget(intval($player), array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ), 'Deal_4_damage_to_a_ground_unit?', 'Choose_a_ground_unit_to_deal_4_damage', 'DEAL_UNIT_DAMAGE|4');
};

// SOR_147 Black One — When Played/When Defeated: You may discard your hand; if you do, draw 3.
$whenPlayedAbilities["SOR_147:0"] =
$whenDefeatedAbilities["SOR_147:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, 'Discard_your_hand_to_draw_3?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_147#0', 1);
};
$customDQHandlers["SOR_147#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID;
    $playerID = intval($player);
    foreach (GetHand(intval($player)) as $h) {
        if (!empty($h->removed)) continue;
        $cid = $h->CardID;
        $h->Remove();
        SWUAddToDiscard(intval($player), $cid, 'HAND');
    }
    DoDrawCard(intval($player), 3);
};

// SOR_163 Star Wing Scout — When Defeated: If you have the initiative, draw 2 cards.
$whenDefeatedAbilities["SOR_163:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $ic = (string)GetInitiativeCounter();
    $holder = (strpos($ic, 'P1') === 0) ? 1 : 2;       // "P1_CLAIMED"/"P1_UNCLAIMED" → 1, else 2
    if ($holder === intval($player)) DoDrawCard(intval($player), 2);
};

// SOR_171 Mission Briefing (event) — draw 2 for the chosen player (OPTIONCHOOSE You/Opponent).
$customDQHandlers["SOR_171#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $target = ($lastDecision === 'Opponent') ? GetOpponent(intval($player)) : intval($player);
    DoDrawCard($target, 2);
};

// SOR_186 No Good to Me Dead — exhaust the chosen unit and flag it "can't ready this round" by its
// UniqueID on its controller (consumed at the next regroup ready step). Already-exhausted target is
// fine: the exhaust is a no-op but the flag still locks it out of the regroup ready.
$customDQHandlers["SOR_186#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $uid  = intval($o->UniqueID ?? 0);
    $ctrl = intval($o->Controller ?? 0);
    OnExhaustCard(intval($player), $lastDecision);
    if ($uid > 0 && $ctrl > 0) AddGlobalEffects($ctrl, 'SWU_CANT_READY_' . $uid);
};

// SOR_115 Agent Kallus — optional draw on the once-per-round defeat trigger. The round's use was
// already consumed at collect time, so declining (NO) just draws nothing.
$customDQHandlers["SOR_115#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};

// SOR_013 Cassian Andor (deployed) — optional draw on the once-per-round base-damage trigger. The
// round's use was consumed at collect time, so declining (NO) just draws nothing.
$customDQHandlers["SOR_013#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};

// ── Batch 6.1: leaders ──────────────────────────────────────────────────────

// SOR_007 Grand Moff Tarkin — deployed leader unit On Attack: You may give an Experience token
// to ANOTHER Imperial unit. $mzID = the attacking Tarkin leader-unit's mzID (excluded by UID).
$onAttackAbilities["SOR_007:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = $self ? intval($self->UniqueID ?? -1) : -1;
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits($selfUID, fn($o) => HasTrait($o->CardID, 'Imperial')),
        'Give_an_Experience_token_to_another_Imperial_unit?', 'Choose_an_Imperial_unit_for_an_Experience_token', 'GIVE_EXPERIENCE|1');
};

// SOR_012 IG-88 — deployed passive ("each other friendly unit gains Raid 1") is already
// implemented in GetConditionalKeyword_Raid_Value (KeywordEffects.php). No handler needed.

// ── Batch 5.1: deck search ──────────────────────────────────────────────────

// SOR_096 Mon Mothma — When Played: Search the top 5 for a REBEL card, reveal it, draw it.
$whenPlayedAbilities["SOR_096:0"] = function($player, $mzID) {
    DoTopDeckSearch(intval($player), 5, fn($c) => HasTrait($c, 'Rebel'), 1);
};

// Universal: give $parts[0] Experience tokens to the chosen unit ($lastDecision).
$customDQHandlers["GIVE_EXPERIENCE"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $n = max(1, intval($parts[0] ?? 1));
    for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $lastDecision);
};

// SOR_037 Academy Defense Walker — When Played: give an Experience token to each friendly DAMAGED unit.
$whenPlayedAbilities["SOR_037:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->Damage ?? 0) > 0) DoGiveExperienceToken(intval($player), $mz);
    }
};

// SOR_231 TIE Advanced / SOR_241 Wing Leader — When Played: 2 Experience to another friendly {trait} unit.
$sorExpToTrait = function($player, $mzID, $trait) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = $self ? intval($self->UniqueID ?? -1) : -1;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->UniqueID ?? -2) === $selfUID) continue;       // "another"
        if (HasTrait($o->CardID, $trait)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_to_another_friendly_" . $trait . "_unit", "GIVE_EXPERIENCE|2");
};
$whenPlayedAbilities["SOR_231:0"] = function($player, $mzID) use ($sorExpToTrait) { $sorExpToTrait($player, $mzID, 'Imperial'); };
$whenPlayedAbilities["SOR_241:0"] = function($player, $mzID) use ($sorExpToTrait) { $sorExpToTrait($player, $mzID, 'Rebel'); };

// SOR_108 Vanguard Infantry — When Defeated: you may give an Experience token to a unit.
$whenDefeatedAbilities["SOR_108:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_an_Experience_token_to_a_unit?", "Give_an_Experience_token_to_a_unit", "GIVE_EXPERIENCE|1");
};

// SOR_049 Obi-Wan Kenobi — When Defeated: 2 Experience to another friendly unit; if it's a Force unit, draw.
$whenDefeatedAbilities["SOR_049:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;            // self is removed (being defeated) → excluded
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_to_another_friendly_unit", "SOR_049#0");
};
$customDQHandlers["SOR_049#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    DoGiveExperienceToken(intval($player), $lastDecision);
    DoGiveExperienceToken(intval($player), $lastDecision);
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Force')) DoDrawCard(intval($player), 1);
};

// SOR_111 Patrolling V-Wing — When Played: draw a card.
$whenPlayedAbilities["SOR_111:0"] = function($player, $mzID) {
    DoDrawCard(intval($player), 1);
};

// SOR_140 SpecForce Soldier — When Played: a unit loses Sentinel for this phase.
// Only units that currently have Sentinel are eligible targets.
$whenPlayedAbilities["SOR_140:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (HasKeyword_Sentinel($o)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_to_lose_Sentinel", "SOR_140#0");
};

// Tag with the bare CardID — drives SWUKeywordSuppressed (via $keywordSuppressors)
// and doubles as the Active Effects UI source.
$customDQHandlers["SOR_140#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, "SOR_140");
};

// Universal: give a Shield token to the unit at $lastDecision (SOR_073 Moment of Peace).
$customDQHandlers["GIVE_SHIELD"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    GiveShieldToken(intval($player), $lastDecision);
};

// SOR_084 Grand Moff Tarkin — "When Played: Search the top 5 cards for up to 2 Imperial cards,
//   reveal and draw them. Put the rest on the bottom in a random order."
$whenPlayedAbilities["SOR_084:0"] = function($player, $mzID) {
    DoTopDeckSearch($player, 5, fn($c) => HasTrait($c, 'Imperial'), 2);
};

// SOR_087 Darth Vader — "When Played: Search the top 10 cards for any number of Villainy units
//   with combined cost 3 or less and play each of them for free."
// WhenPlayed triggers fire before Ambush (TOPDECKSEARCH queued at priority 1).
// Units are placed directly in arena — no WhenPlayed triggers on the free-played units.
$whenPlayedAbilities["SOR_087:0"] = function($player, $mzID) {
    DoTopDeckPlay($player, 10,
        fn($c) => strpos(CardAspect($c) ?? '', 'Villainy') !== false && CardType($c) === 'Unit',
        3
    );
};

// SOR_236 R2-D2 — "When Played/On Attack: Look at the top card of your deck.
//   You may put it on the bottom of your deck."
$whenPlayedAbilities["SOR_236:0"] =
$onAttackAbilities["SOR_236:0"]   = function($player, $mzID) {
    DoScry($player, 1);
};

// SOR_119 Reinforcement Walker — "When Played/On Attack: Look at the top card of your deck.
//   Either draw that card or discard it and heal 3 damage from your base."
// Mandatory either/or → OPTIONCHOOSE (no decline). Fizzles with no decision on an empty deck.
$whenPlayedAbilities["SOR_119:0"] =
$onAttackAbilities["SOR_119:0"]   = function($player, $mzID) {
    $topIdx = _SWUTopDeckFrontIdx(intval($player));
    if ($topIdx === -1) return;                                // no top card to look at
    $topID  = GetDeck(intval($player))[$topIdx]->CardID;       // shown to the acting player only
    // Single-word option labels — OPTIONCHOOSE params are space-delimited in storage; the tooltip
    // carries the full meaning. Leading "@CardID" shows the card being looked at.
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Draw&Discard", 1, "Draw_the_top_card,_or_discard_it_and_heal_3_from_your_base");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_119#0", 1);
};

// Receives the OPTIONCHOOSE label. Both branches act on the top (front) card just looked at.
$customDQHandlers["SOR_119#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision === 'Draw') {
        SWUDrawTopCardFront(intval($player));
    } else {
        // "Discard and heal 3"
        SWUMillTopCard(intval($player));
        OnHealBase(intval($player), intval($player), 3);
    }
};

// SOR_192 Ezra Bridger — "When this unit completes an attack: Look at the top card of your deck.
//   You may play it, discard it, or leave it on top of your deck."
// First consumer of the On Attack End trigger (generator now maps "completes an attack:" → onAttackEnd).
// Three named choices → OPTIONCHOOSE; fizzles with no decision on an empty deck.
$onAttackEndAbilities["SOR_192:0"] = function($player, $mzID) {
    $topIdx = _SWUTopDeckFrontIdx(intval($player));
    if ($topIdx === -1) return;                                // no top card to look at
    $topID  = GetDeck(intval($player))[$topIdx]->CardID;       // shown to the acting player only
    // Single-word option labels (OPTIONCHOOSE params are space-delimited in storage); "Leave" =
    // leave it on top. Leading "@CardID" shows the card being looked at; tooltip carries meaning.
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Play&Discard&Leave", 1, "Play_the_top_card,_discard_it,_or_leave_it_on_top");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_192#0", 1);
};

$customDQHandlers["SOR_192#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision === 'Discard') {
        SWUMillTopCard(intval($player));
    } elseif ($lastDecision === 'Play') {
        // Play the top card paying its normal cost. SWUPlayTopDeckCard does NOT advance the turn —
        // the parent attack action (combat) owns SWUAfterAction. Unaffordable → no-op (stays on top).
        SWUPlayTopDeckCard(intval($player));
    }
    // "Leave it on top" → no-op.
};

// SOR_238 C-3PO — "When Played/On Attack: Choose a number, then look at the top card of your deck.
//   If its cost is the chosen number, you may reveal and draw it. (Otherwise, leave it on top.)"
// The number is chosen BLIND (before looking), so no card is shown on the NUMBERCHOOSE.
$whenPlayedAbilities["SOR_238:0"] =
$onAttackAbilities["SOR_238:0"]   = function($player, $mzID) {
    if (_SWUTopDeckFrontIdx(intval($player)) === -1) return;   // empty deck → nothing to look at
    DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "0|10", 1, "Choose_a_number_(a_card_cost)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_238#0", 1);
};

// Receives the chosen number; the player ALWAYS gets to look at the top card (the "@CardID" image
// is shown either way). If its cost matches the chosen number, offer reveal-and-draw; otherwise the
// player just acknowledges the peek and it stays on top (you looked, but can't do anything with it).
$customDQHandlers["SOR_238#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $idx = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $topID  = GetDeck(intval($player))[$idx]->CardID;
    $chosen = intval($lastDecision);
    if (intval(CardCost($topID)) === $chosen) {
        DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Draw&Leave", 1, "Cost_matches_-_reveal_and_draw_the_top_card,_or_leave_it_on_top");
    } else {
        // Whiff: still let the player peek the card; the only outcome is to leave it on top.
        DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&OK", 1, "Top_card_cost_does_not_match_-_it_stays_on_top");
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_238#1", 1);
};

// Reveal-and-draw step: "Draw" reveals (public) then draws the top card; "Leave" is a no-op.
$customDQHandlers["SOR_238#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'Draw') return;
    global $playerID;
    $playerID = intval($player);
    $idx = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $topID = GetDeck(intval($player))[$idx]->CardID;
    AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . GameLogCardRef($topID) . ' and drew it');
    SWUDrawTopCardFront(intval($player));
};

// SOR_246 You're My Only Hope — "Play" the top card for 5 less, or free if your base has 5 or less
// remaining HP; "Leave" is a no-op. Event flow's FINISH_PLAY_CARD owns SWUAfterAction, so the play
// must not advance the turn → SWUPlayTopDeckCard (capture/restore turn state around ActivateCard).
$customDQHandlers["SOR_246#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'Play') return;                  // "Leave" → no-op
    global $playerID;
    $playerID = intval($player);
    $bases = GetBase(intval($player));
    $free  = false;
    if (!empty($bases)) {
        $remaining = intval(CardHp($bases[0]->CardID)) - intval($bases[0]->Damage);
        if ($remaining <= 5) $free = true;
    }
    SWUPlayTopDeckCard(intval($player), $free, $free ? 0 : 5);
};

// Universal: discard the chosen card ($lastDecision = "theirHand-N") from the opponent's hand to
// the opponent's discard (From=HAND). Used by the "look at an opponent's hand and discard a card
// from it" family (SOR_200, SOR_201).
$customDQHandlers["DISCARD_FROM_OPP_HAND"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);                    // theirHand-N → the opponent's hand card
    if ($obj === null || !empty($obj->removed)) return;
    $opp    = OtherPlayer(intval($player));
    $cardID = $obj->CardID;
    $obj->Remove();
    SWUAddToDiscard($opp, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
};

// Universal no-op acknowledge handler (the "OK" button on an information-only popup).
$customDQHandlers["ACK"] = function($player, $parts, $lastDecision) { /* acknowledge — no effect */ };

// SOR_228 Viper Probe Droid — "When Played: Look at an opponent's hand." (information only)
// Shows the opponent's hand to the player as an acknowledge popup (card images + an OK button).
$whenPlayedAbilities["SOR_228:0"] = function($player, $mzID) {
    SWULookAtOpponentHand(intval($player));      // logs the reveal (visible to both players for now)
    SWUQueueShowOpponentHand(intval($player));   // present the hand as an acknowledge popup
};

// SOR_201 Bodhi Rook — "When Played: Look at an opponent's hand and discard a non-unit card from it."
$whenPlayedAbilities["SOR_201:0"] = function($player, $mzID) {
    $targets = SWULookAtOpponentHand(intval($player), fn($cid) => stripos(CardType($cid) ?? '', 'unit') === false);
    // Queue the discard first: with 2+ legal targets it's an MZCHOOSE (which already presents the
    // hand); with 0 or 1 it auto-resolves with no choice, so the player never sees the hand. In that
    // no-MZCHOOSE case, SAVE a snapshot of the hand NOW (still pre-discard, since the queued discard
    // hasn't executed yet) and show it Viper-Probe-Droid style (SOR_228) AFTER the auto-discard
    // resolves — the saved snapshot still shows the discarded card so the player can confirm OK.
    SWUQueueChooseTarget(intval($player), $targets, "Discard_a_non-unit_card_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
    if (count($targets) <= 1) SWUQueueShowOpponentHand(intval($player));
};

// SOR_190 Lothal Insurgent — "When Played: If you played another card this phase, each opponent
// draws a card then discards a random card from their hand." The SWU_CARDS_PLAYED counter includes
// Lothal itself, so >1 means another card was played.
$whenPlayedAbilities["SOR_190:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_CARDS_PLAYED') <= 1) return;
    $opp = OtherPlayer(intval($player));   // 2-player: the one opponent (Twin Suns: each opponent)
    DoDrawCard($opp, 1);
    $hand = &GetHand($opp);
    $liveIdx = [];
    foreach ($hand as $i => $c) { if (empty($c->removed)) $liveIdx[] = $i; }
    if (empty($liveIdx)) return;
    $pick = $liveIdx[array_rand($liveIdx)];
    $cid  = $hand[$pick]->CardID;
    $hand[$pick]->Remove();
    SWUAddToDiscard($opp, $cid, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', "P{$opp} drew a card and discarded " . GameLogCardRef($cid) . ' at random');
};

// SOR_191 Vanguard Ace — "When Played: For each other card you played this phase, give an Experience
// token to this unit." Counter includes Vanguard itself, so "other" = count - 1.
$whenPlayedAbilities["SOR_191:0"] = function($player, $mzID) {
    $others = max(0, GlobalEffectCount(intval($player), 'SWU_CARDS_PLAYED') - 1);
    for ($i = 0; $i < $others; $i++) DoGiveExperienceToken(intval($player), $mzID);
};

// SOR_051 Luke Skywalker — "When Played: Give an enemy unit -3/-3 for this phase. If a friendly
// unit was defeated this phase, give that enemy unit -6/-6 for this phase instead." (Restore 3 is
// keyword-wired.) The friendly-defeated condition reads the SWU_FRIENDLY_DEFEATED phase flag.
$whenPlayedAbilities["SOR_051:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $enemies = array_merge(
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    );
    $amount = GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') > 0 ? 6 : 3;
    SWUQueueChooseTarget(intval($player), $enemies,
        "Give_an_enemy_unit_-{$amount}/-{$amount}_for_this_phase", "APPLY_PHASE_DEBUFF|{$amount}|{$amount}|SOR_051");
};

// SOR_062 Regional Governor — "When Played: Name a card. While this unit is in play, opponents
// can't play the named card." Stores the named title (keyed by this unit's UID) as a GlobalEffects
// flag on the controller; SWUCardPlayBlocked consults it (and clears it when the unit leaves play).
$whenPlayedAbilities["SOR_062:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    $uid = $obj ? intval($obj->UniqueID ?? 0) : 0;
    if ($uid === 0) return;
    DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card_opponents_cannot_play");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_062#0|{$uid}", 1);
};

$customDQHandlers["SOR_062#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $uid = intval($parts[0] ?? 0);
    if ($uid === 0) return;
    $name    = trim($lastDecision);
    $encName = str_replace(' ', '_', $name);     // GlobalEffects flags are space-delimited
    AddGlobalEffects(intval($player), "SWU_NAMEBLOCK|{$uid}|{$encName}");
    AddGameLogEntry('NAMECARD', 'P' . intval($player) . ' named ' . $name . " (opponents can't play it)", 'ALL');
};

// SOR_185 Chimaera — "On Attack: Name a card. An opponent reveals their hand and discards a card
// with that name from it." First server-side consumer of the NAMECARD decision.
$onAttackAbilities["SOR_185:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_185#0", 1);
};

// Receives the named card NAME ($lastDecision is the card title, e.g. "Mission Briefing"). The
// opponent reveals their hand (public), then discards ONE card whose title matches that name.
$customDQHandlers["SOR_185#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $namedName = trim($lastDecision);
    $opp       = OtherPlayer(intval($player));
    $oppHand   = &GetHand($opp);

    // "An opponent reveals their hand" — public reveal of the whole hand.
    $refs = [];
    foreach ($oppHand as $card) { if (empty($card->removed)) $refs[] = GameLogCardRef($card->CardID); }
    AddGameLogEntry('REVEAL', "P{$opp} revealed their hand: " . (empty($refs) ? '(empty)' : implode(', ', $refs)), 'ALL');
    AddGameLogEntry('NAMECARD', 'P' . intval($player) . ' named ' . $namedName, 'ALL');

    // Show the opponent's hand to the player as an acknowledge popup (SOR_201 Bodhi Rook style).
    // Queue it BEFORE the inline discard so the snapshot captures the PRE-discard hand; the popup
    // resolves AFTER this handler returns, so on a hit the player sees the discarded card to confirm,
    // and on a whiff (no match) they see the full unchanged hand. The discard always auto-resolves
    // (copies are identical → no MZCHOOSE), so this is the only way the player would ever see the hand.
    SWUQueueShowOpponentHand(intval($player));

    // "discards a card with that name from it" — the first matching copy (by card title).
    foreach ($oppHand as $card) {
        if (empty($card->removed) && CardTitle($card->CardID) === $namedName) {
            $cid = $card->CardID;
            $card->Remove();
            SWUAddToDiscard($opp, $cid, 'HAND');
            DecisionQueueController::CleanupRemovedCards();
            AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cid) . " from P{$opp}'s hand", 'ALL');
            break;
        }
    }
};

// SOR_031 Inferno Four — "When Played/When Defeated: Look at the top 2 cards of your deck.
//   Put any number of them on the bottom and the rest on top in any order."
$whenPlayedAbilities["SOR_031:0"] =
$whenDefeatedAbilities["SOR_031:0"] = function($player, $mzID) {
    DoScry($player, 2);
};

// SOR_148 Guerilla Attack Pod — "When Played: If a base has 15 or more damage on it, ready this unit."
$whenPlayedAbilities["SOR_148:0"] = function($player, $mzID) {
    $triggered = false;
    foreach ([1, 2] as $p) {
        foreach (GetBase($p) as $b) {
            if (!empty($b->removed)) continue;
            if (intval($b->Damage) >= 15) { $triggered = true; break 2; }
        }
    }
    if ($triggered) OnReadyCard($player, $mzID);
};

// SOR_189 Leia Organa (Defiant Princess) — "When Played: Either ready a resource or exhaust a unit."
// Mandatory either/or → OPTIONCHOOSE with two labeled buttons (no decline).
// "Ready a resource" → auto-ready the first exhausted resource (no further player choice).
// "Exhaust a unit"   → exhaust a unit; auto-picks when only 1 other ready unit exists.
$whenPlayedAbilities["SOR_189:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ready a resource&Exhaust a unit", 1, "Ready_a_resource_or_exhaust_a_unit?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_189#0|{$mzID}", 1);
};

// Receives the OPTIONCHOOSE label. $parts[0] = Leia's own arena mzID (excluded from exhaust targets).
$customDQHandlers["SOR_189#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $leiaMzID  = $parts[0] ?? '';

    if ($lastDecision === "Ready a resource") {
        // Ready the first exhausted resource belonging to this player.
        $resources = GetResources($player);
        $target = null;
        for ($i = 0; $i < count($resources); $i++) {
            if (!empty($resources[$i]->removed)) continue;
            if (intval($resources[$i]->Status) === 0) {
                $target = "myResources-{$i}";
                break;
            }
        }
        if ($target === null) return;
        DecisionQueueController::AddDecision($player, "PASSPARAMETER", $target, 0);
        DecisionQueueController::AddDecision($player, "CUSTOM", "READY_RESOURCE", 0);
    } else {
        // Exhaust a unit — collect all ready units (Status=1) except Leia herself.
        $targets = [];
        foreach (array_merge(
            ZoneSearch("myGroundArena",   AnyUnitFilter),
            ZoneSearch("mySpaceArena",    AnyUnitFilter),
            ZoneSearch("theirGroundArena",AnyUnitFilter),
            ZoneSearch("theirSpaceArena", AnyUnitFilter)
        ) as $mz) {
            if ($mz === $leiaMzID) continue;
            $obj = GetZoneObject($mz);
            if ($obj === null || !empty($obj->removed)) continue;
            if (intval($obj->Status) !== 1) continue;
            $targets[] = $mz;
        }
        if (empty($targets)) return;
        if (count($targets) === 1) {
            DecisionQueueController::AddDecision($player, "PASSPARAMETER", $targets[0], 0);
        } else {
            // Leave $playerID set — ExecuteStaticMethods calls MZCountChoices immediately after return.
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 0);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EXHAUST_UNIT", 0);
    }
};

// SOR_162 / SHD_166 Disabling Fang Fighter — "When Played: You may defeat an upgrade."
// Routes through the generic SWUQueueDefeatUpgrade helper (host pick-or-pass).
$whenPlayedAbilities["SOR_162:0"] =
$whenPlayedAbilities["SHD_166:0"] = function($player, $mzID) {
    SWUQueueDefeatUpgrade(intval($player), "Choose_a_unit_to_defeat_its_upgrade", may: true, max: 1);
};

// ── Generic defeat-upgrade resolution (shared by SOR_162/SHD_166/SOR_251/SHD_262/SOR_170)
// DEFEAT_UPGRADE receives the chosen HOST unit mzID (or '-' on a may-decline / fizzle).
// ── Generic "take control of an upgrade and attach it to a different eligible unit" (JTL_056 non-Pilot,
// JTL_242 token). Stages every matching upgrade across ALL units into TempZone for a single MAY pick
// (tempZone-N → "hostMz:subIdx" via the MoveUpgMap var), then a destination-unit pick, then moves it. ──
// $sourceHostMz: if non-empty, only scan that one host's upgrades ("an upgrade ON THIS unit" — JTL_070).
// $destScope:    '' = any unit (default); 'friendlyVehicle' = restrict the destination to another
//                friendly Vehicle unit (JTL_070). Read back in MOVE_UPGRADE.
function SWUQueueMoveUpgrade(int $player, string $filter, string $tooltip, string $sourceHostMz = '', string $destScope = ''): void {
    global $playerID;
    $playerID = intval($player);
    $entries = []; // [hostMz, subIdx, cardID]
    $scanZones = ($sourceHostMz !== '')
        ? [$sourceHostMz]
        : ['myGroundArena','mySpaceArena','theirGroundArena','theirSpaceArena'];
    foreach ($scanZones as $z) {
        // A single host mzID is fetched directly; a zone name is enumerated.
        $mzList = ($sourceHostMz !== '') ? [$z] : ZoneSearch($z, AnyUnitFilter);
        foreach ($mzList as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed) || !is_array($o->Subcards ?? null)) continue;
            foreach ($o->Subcards as $i => $sub) {
                if (_SWUUpgradeMatchesMoveFilter($sub, $filter)) {
                    $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
                    $entries[] = [$mz, $i, $scid];
                }
            }
        }
    }
    if (empty($entries)) return; // no movable upgrade → fizzle
    DecisionQueueController::StoreVariable("MoveUpgDestScope", $destScope);
    $temp = &GetTempZone($player); while (count($temp) > 0) array_pop($temp);
    $map = [];
    foreach ($entries as $e) { AddTempZone($player, $e[2]); $map[] = $e[0] . ':' . $e[1]; }
    DecisionQueueController::StoreVariable("MoveUpgMap", implode(",", $map));
    $tempMZs = [];
    for ($k = 0; $k < count($entries); $k++) $tempMZs[] = "myTempZone-$k";
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $tempMZs), 1, tooltip: $tooltip);
    DecisionQueueController::AddDecision($player, "CUSTOM", "MOVE_UPGRADE", 1);
    // Leave $playerID set: MZCountChoices validates the myTempZone-* specs next, under it.
}
// Upgrade chosen → pick the destination unit (any unit except the source host).
$customDQHandlers["MOVE_UPGRADE"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $drain = function() use ($player) { $t = &GetTempZone($player); while (count($t) > 0) array_pop($t); };
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') { $drain(); return; }
    $map = explode(",", (string)DecisionQueueController::GetVariable("MoveUpgMap"));
    if (!preg_match('/-(\d+)$/', $lastDecision, $m)) { $drain(); return; }
    $n = intval($m[1]);
    $drain();
    if (!isset($map[$n])) return;
    [$hostMz, $subIdx] = array_pad(explode(':', $map[$n], 2), 2, '');
    if ($hostMz === '' || $subIdx === '') return;
    DecisionQueueController::StoreVariable("MoveUpgSrc", $hostMz . '|' . $subIdx);
    $destScope = (string)DecisionQueueController::GetVariable("MoveUpgDestScope");
    $dests = [];
    foreach (['myGroundArena','mySpaceArena','theirGroundArena','theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            if ($mz === $hostMz) continue;
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            if ($destScope === 'friendlyVehicle') {
                if (intval($o->Controller ?? 0) !== intval($player)) continue;
                if (!HasTrait($o->CardID ?? '', 'Vehicle')) continue;
            }
            $dests[] = $mz;
        }
    }
    if (empty($dests)) return; // nowhere else to attach it → fizzle (upgrade stays put)
    SWUQueueChooseTarget(intval($player), $dests, "Attach_it_to_a_different_eligible_unit", "MOVE_UPGRADE#1");
};
// Destination chosen → move the upgrade there (taking control of it).
$customDQHandlers["MOVE_UPGRADE#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    [$hostMz, $subIdx] = array_pad(explode('|', (string)DecisionQueueController::GetVariable("MoveUpgSrc"), 2), 2, '');
    if ($hostMz === '' || $subIdx === '') return;
    SWUMoveUpgradeCrossUnit($hostMz, intval($subIdx), $lastDecision, intval($player));
};

// JTL_056 Hondo Ohnaka — Shielded + On Attack: take control of a non-Pilot upgrade and move it.
$onAttackAbilities["JTL_056:0"] = function($player, $mzID) {
    SWUQueueMoveUpgrade(intval($player), 'nonpilot', "Take_control_of_a_non-Pilot_upgrade_to_move_it");
};

// JTL_242 Shuttle ST-149 — Shielded + When Played/When Defeated: take control of a token upgrade and move it.
$whenPlayedAbilities["JTL_242:0"] = $whenDefeatedAbilities["JTL_242:0"] = function($player, $mzID) {
    SWUQueueMoveUpgrade(intval($player), 'token', "Take_control_of_a_token_upgrade_to_move_it");
};

// JTL_213 Sidon Ithano — When played as a unit: You may attach this unit as an upgrade to an enemy
// Vehicle unit without a Pilot on it. (Becomes a pilot on the enemy ship — it buffs the enemy host;
// that is what the card does.)
$whenPlayedAbilities["JTL_213:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || !empty($self->removed)) return;
    $uid = intval($self->UniqueID ?? 0);
    $targets = [];
    foreach (['theirGroundArena','theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            if (!HasTrait($o->CardID ?? '', 'Vehicle')) continue;
            if (_SWUFindPilotSubcard($o) !== null) continue; // already has a pilot
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Attach_Sidon_to_an_enemy_Vehicle", "Choose_an_enemy_Vehicle", "JTL_213#0|" . $uid);
};
$customDQHandlers["JTL_213#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $selfMz = SWUFindMzByUID($uid);
    if ($selfMz === null) return;
    SWUMoveUnitToUpgrade($selfMz, $lastDecision, true); // attach Sidon as a Pilot onto the enemy Vehicle
};

// JTL_260 Death Star Plans (upgrade) — "When attached unit is attacked: the attacking player takes
// control of this upgrade and attaches it to a unit they control." Fired for the ATTACKER; $defenderMzID
// is the attacked host (attacker's frame). Routed through an intermediate CUSTOM so the destination
// MZCHOOSE's MZCountChoices runs under the attacker (not after DispatchTrigger restores $playerID).
$onAttackedFromUpgradeAbilities["JTL_260"] = function($attacker, $defenderMzID) {
    global $playerID;
    $playerID = intval($attacker);
    $host = GetZoneObject($defenderMzID);
    if ($host === null || !empty($host->removed)) return;
    $hostUid = intval($host->UniqueID ?? 0);
    DecisionQueueController::StoreVariable("JTL260HostUID", (string)$hostUid);
    DecisionQueueController::AddDecision($attacker, 'CUSTOM', 'JTL_260#0', 1);
};
$customDQHandlers["JTL_260#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hostUid = intval(DecisionQueueController::GetVariable("JTL260HostUID"));
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz === null) return;
    // attacker's own units = destinations (they always control at least the attacker)
    $dests = array_values(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)));
    if (empty($dests)) return;
    SWUQueueChooseTarget(intval($player), $dests, "Attach_Death_Star_Plans_to_a_unit_you_control", "JTL_260#1");
};
$customDQHandlers["JTL_260#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $hostUid = intval(DecisionQueueController::GetVariable("JTL260HostUID"));
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz === null) return;
    $host = GetZoneObject($hostMz);
    if ($host === null || !is_array($host->Subcards ?? null)) return;
    // locate JTL_260's real subcard index on the host
    $subIdx = -1; $cnt = 0;
    foreach ($host->Subcards as $sub) {
        $isCap = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
        $isRem = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
        if ($isCap || $isRem) continue;
        $scid  = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        if ($scid === 'JTL_260') { $subIdx = $cnt; }
        $cnt++;
    }
    // subIdx here counts only non-captive/non-removed subcards — the same index space SWUMoveUpgradeCrossUnit
    // splices on is the raw Subcards array; re-find the raw index to be safe.
    $rawIdx = -1;
    foreach ($host->Subcards as $k => $sub) {
        $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        $isRem = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
        if (!$isRem && $scid === 'JTL_260') { $rawIdx = $k; break; }
    }
    if ($rawIdx < 0) return;
    SWUMoveUpgradeCrossUnit($hostMz, $rawIdx, $lastDecision, intval($player));
};

// JTL_083 Pantoran Starship Thief — "When Played: You may pay 3 resources. If you do, attach this unit as
// an upgrade to a Fighter or Transport unit without a Pilot on it. Take control of that unit." The detach-
// returns-control half is handled at the SWUDefeatUpgrade chokepoint (shared with SOR_122).
$whenPlayedAbilities["JTL_083:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || !empty($self->removed)) return;
    if (SWUResourceCount(intval($player), true) < 3) return; // can't pay → no offer
    $uid = intval($self->UniqueID ?? 0);
    // Fighter/Transport units (any owner) without a Pilot already on them.
    $targets = [];
    foreach (['myGroundArena','mySpaceArena','theirGroundArena','theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            if (!HasTrait($o->CardID ?? '', 'Fighter') && !HasTrait($o->CardID ?? '', 'Transport')) continue;
            if (_SWUFindPilotSubcard($o) !== null) continue;
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Pay_3_to_attach_to_and_take_control_of_a_Fighter/Transport", "Choose_a_Fighter_or_Transport", "JTL_083#0|" . $uid);
};
$customDQHandlers["JTL_083#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 3) return;
    $uid = intval($parts[0] ?? 0);
    $selfMz = SWUFindMzByUID($uid);
    if ($selfMz === null) return;
    $hostObj = GetZoneObject($lastDecision);
    if ($hostObj === null || !empty($hostObj->removed)) return;
    $hostUid = intval($hostObj->UniqueID ?? 0);
    SWUPayCost(intval($player), 3, 0);
    SWUMoveUnitToUpgrade($selfMz, $lastDecision, true);  // attach Pantoran Thief as a Pilot
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz !== null) SWUTakeControlOfUnit(intval($player), $hostMz); // take control of the host
};

$customDQHandlers["DEFEAT_UPGRADE"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        DecisionQueueController::CleanupRemovedCards();
        return;
    }
    global $playerID;
    $playerID = intval($player);
    _SWUResolveDefeatUpgradeHost(intval($player), $lastDecision);
    // leave $playerID set: _SWUResolveDefeatUpgradeHost may queue a relative-mzID pick,
    // and MZCountChoices runs immediately after and resolves myTempZone-* under $playerID.
};

// DEFEAT_UPGRADE#1 receives the staged-upgrade pick(s): a single mzID (MZCHOOSE/MZMAYCHOOSE)
// or an &-delimited list (MZMULTICHOOSE), or '-'/'' for "defeat none" (valid when $min==0).
// myTempZone-N maps positionally to the real GetUpgradesOnUnit index $matchIdx[N].
$customDQHandlers["DEFEAT_UPGRADE#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);

    $host   = (string)DecisionQueueController::GetVariable("DefeatUpgHost");
    $idxRaw = (string)DecisionQueueController::GetVariable("DefeatUpgIdx");
    $matchIdx = ($idxRaw === '') ? [] : array_map('intval', explode(",", $idxRaw));

    $drain = function() use ($player) {
        $temp = &GetTempZone($player);
        while (count($temp) > 0) array_pop($temp);
    };

    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        $drain();
        DecisionQueueController::CleanupRemovedCards();
        return;
    }

    // map picked myTempZone-N → real subcard indices
    $realIdx = [];
    foreach (explode('&', $lastDecision) as $mz) {
        $mz = trim($mz);
        if ($mz === '' || $mz === '-') continue;
        if (preg_match('/-(\d+)$/', $mz, $m)) {
            $n = intval($m[1]);
            if (isset($matchIdx[$n])) $realIdx[] = $matchIdx[$n];
        }
    }
    // descending so defeating a higher index never renumbers the lower ones still to come
    $realIdx = array_unique($realIdx);
    rsort($realIdx);
    foreach ($realIdx as $idx) {
        SWUDefeatUpgrade($player, $host, $idx);
    }
    $drain();
    DecisionQueueController::CleanupRemovedCards();
    // Chain the next "may defeat 1" link if one was armed (SOR_155 "defeat up to 2 upgrades"). Read-
    // and-clear so the dispatched link doesn't re-trigger; it re-reads the board (so picks span units).
    $then = (string)DecisionQueueController::GetVariable("DefeatUpgThen");
    if ($then !== '') {
        DecisionQueueController::StoreVariable("DefeatUpgThen", "");
        global $customDQHandlers;
        // Pass the host mzID so a thenHandler that acts on the host (JTL_175 "deal 1 to that unit")
        // gets it directly; chain handlers (SOR_155) ignore $parts and re-read the board.
        if (isset($customDQHandlers[$then])) $customDQHandlers[$then]($player, [$host], '');
    }
};

// ── SOR_224 Change of Heart — event steal handler ───────────────────────────
// Receives chosen unit mzID from MZCHOOSE/PASSPARAMETER.
// Moves it to $player's arena and marks it TEMPORARY_STEAL so RegroupPhaseStart
// returns it to its owner.
$customDQHandlers["SOR_224#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-') return;
    global $playerID;
    $playerID = intval($player);
    $newMzID = SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($newMzID !== '') {
        AddTurnEffect($newMzID, 'TEMPORARY_STEAL');
    }
};

// ── SOR_006 Emperor Palpatine — Leader front-side ability ────────────────────
// Entry: $leaderAbilities["SOR_006"] (LeaderAbilities.php) — Action [1 resource,
// Exhaust, Defeat a friendly unit]: deal 1 damage to a unit and draw a card.
//   (plain): defeat the sacrifice, then deal 1 to a chosen unit
//   #1: draw
$customDQHandlers["SOR_006#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    $targets = array_values(array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ));
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
        ZoneSearch("myGroundArena",    ["Unit"]),
        ZoneSearch("mySpaceArena",     ["Unit"]),
        ZoneSearch("theirGroundArena", ["Unit"]),
        ZoneSearch("theirSpaceArena",  ["Unit"])
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
    $friendlies = array_values(array_filter(array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ), fn($mz) => $mz !== $mzID));
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
    $targets = array_values(array_filter(array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    ), fn($mz) => $mz !== $attackerMzID));
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
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    $targets = array_values(array_merge(
        ZoneSearch("myGroundArena",    AnyUnitFilter),
        ZoneSearch("mySpaceArena",     AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    ));
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

// ── JTL_036 Iden Versio — OnAttached ─────────────────────────────────────────
// "When this upgrade attaches to a unit: Give a Shield token to that unit."
// $mzID is the host unit's arena mzID. The unit-side Shielded keyword does NOT fire here —
// JTL_036 enters as an upgrade (pilot), not as a unit.
$onAttachedAbilities["JTL_036:0"] = function($player, $mzID) {
    GiveShieldToken(intval($player), $mzID);
};

// ── SOR_122 Traitorous — OnAttached ──────────────────────────────────────────
// "When this upgrade becomes attached to a non-leader unit that costs 3 or less:
// Take control of that unit." (The "becomes unattached → return control" half is
// handled at the SWUDefeatUpgrade chokepoint in CombatLogic.php ~248-255.)
$onAttachedAbilities["SOR_122:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || ($host->removed ?? false)) return;
    if (intval(CardCost($host->CardID) ?? 99) <= 3) {
        SWUTakeControlOfUnit(intval($player), $mzID);
    }
};

// Shared helper: given a known host unit mzID, compute its filter-matching upgrades
// (as real GetUpgradesOnUnit indices), then either auto-defeat the single mandatory
// match, or stage them into TempZone and queue the right pick decision by $min/$max.
// Reads $min|$max|$filter from the DefeatUpgParams DQ variable (set by SWUQueueDefeatUpgrade).
// Leaves $playerID set so MZCountChoices resolves the myTempZone-* specs.
function _SWUResolveDefeatUpgradeHost(int $player, string $hostMzID): void {
    global $playerID;
    $playerID = intval($player);

    $host = GetZoneObject($hostMzID);
    if ($host === null || ($host->removed ?? false)) {
        DecisionQueueController::CleanupRemovedCards();
        return;
    }

    [$min, $max, $filter] = array_pad(
        explode('|', (string)DecisionQueueController::GetVariable("DefeatUpgParams"), 3), 3, '');
    $min = intval($min);
    $max = intval($max);

    // matching upgrades as real GetUpgradesOnUnit indices (the index space SWUDefeatUpgrade expects)
    $upgrades = GetUpgradesOnUnit($host);
    $matchIdx = [];
    foreach ($upgrades as $i => $up) {
        if (SWUUpgradeMatchesFilter($up->CardID ?? '', $filter)) $matchIdx[] = $i;
    }
    $count = count($matchIdx);
    if ($count === 0) { DecisionQueueController::CleanupRemovedCards(); return; }

    // mandatory single match → auto-defeat, no picker
    if ($min >= 1 && $count === 1) {
        SWUDefeatUpgrade($player, $hostMzID, $matchIdx[0]);
        DecisionQueueController::CleanupRemovedCards();
        // Honour the continuation (JTL_175 "deal 1 to that unit", SOR_155 chain) even on the
        // auto-defeat path — pass the host mzID, same as the interactive DEFEAT_UPGRADE#1 path.
        $then = (string)DecisionQueueController::GetVariable("DefeatUpgThen");
        if ($then !== '') {
            DecisionQueueController::StoreVariable("DefeatUpgThen", "");
            global $customDQHandlers;
            if (isset($customDQHandlers[$then])) $customDQHandlers[$then]($player, [$hostMzID], '');
        }
        return;
    }

    // stage matching upgrades into TempZone IN $matchIdx ORDER (myTempZone-k ↔ $matchIdx[k])
    $temp = &GetTempZone($player);
    while (count($temp) > 0) array_pop($temp);
    foreach ($matchIdx as $i) {
        AddTempZone($player, $upgrades[$i]->CardID ?? '-');
    }
    $tempMZs = [];
    for ($k = 0; $k < $count; $k++) $tempMZs[] = "myTempZone-" . $k;

    DecisionQueueController::StoreVariable("DefeatUpgHost", $hostMzID);
    DecisionQueueController::StoreVariable("DefeatUpgIdx", implode(",", $matchIdx));

    if ($max <= 1) {
        // single pick → card-image popup (Mode=None routes myTempZone-N to ShowMZChoosePopup)
        $type = ($min === 0) ? "MZMAYCHOOSE" : "MZCHOOSE";
        DecisionQueueController::AddDecision($player, $type, implode("&", $tempMZs), 1,
            tooltip: "Choose_an_upgrade_to_defeat");
    } else {
        // multi pick → MZMultiChooseUI modal (Select All / Clear). effectiveMax = count → Select All shows.
        $effectiveMax = min($max, $count);
        DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
            $min . "|" . $effectiveMax . "|" . implode("&", $tempMZs), 1,
            tooltip: "Choose_upgrades_to_defeat");
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEFEAT_UPGRADE#1", 1);
}

// ── SOR_016 Grand Admiral Thrawn ─────────────────────────────────────────────
// Deployed OnAttack: "You may reveal the top card of any player's deck.
// Exhaust a unit that costs the same as or less than the revealed card."

$onAttackAbilities["SOR_016:0"] = function($player) {
    global $playerID;
    $playerID = $player;
    DecisionQueueController::AddDecision($player, 'YESNO', '', 1, 'Use_Thrawn_ability?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_016#2', 1);
};

// YES: proceed to deck choice. NO: return (combat continuation already queued).
$customDQHandlers["SOR_016#2"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'YESNO', '', 1, 'Own_deck_or_opponent?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_016#0|attack', 1);
};

// Shared handler: YES = own deck, NO = opponent's deck.
// Peeks top card, finds units with cost <= that card's cost, queues PASSPARAMETER or MZCHOOSE.
// Context param 'action' calls SWUAfterAction when done; 'attack' does not (combat handles it).
$customDQHandlers["SOR_016#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $context = $parts[0] ?? 'action';

    $deckOwner = ($lastDecision === 'YES') ? intval($player) : OtherPlayer(intval($player));

    // Top card = first non-removed entry (matches the APS handler's filtering).
    $deck   = GetDeck($deckOwner);
    $topIdx = null;
    foreach ($deck as $i => $c) {
        if (empty($c->removed ?? false)) { $topIdx = $i; break; }
    }
    if ($topIdx === null) {
        if ($context === 'action') SWUAfterAction($player);
        return;
    }

    $topCard = $deck[$topIdx];
    $topCost = intval(CardCost($topCard->CardID));

    // Reveal top card (cosmetic flash message).
    $savedPID = $playerID;
    $playerID = $deckOwner;
    DoRevealCard($deckOwner, "myDeck-" . $topIdx);
    $playerID = $savedPID;
    AddGameLogEntry(
        'REVEAL',
        'Grand Admiral Thrawn reveals: ' . GameLogCardRef($topCard->CardID)
    );

    // Collect all units with cost <= top card cost.
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $zone) {
        foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
            $obj = GetZoneObject($mz);
            if ($obj === null || ($obj->removed ?? false)) continue;
            if (intval(CardCost($obj->CardID)) <= $topCost) $targets[] = $mz;
        }
    }

    if (empty($targets)) {
        if ($context === 'action') SWUAfterAction($player);
        return;
    }

    if (count($targets) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
            'Exhaust_a_unit_costing_at_most_' . $topCost);
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_016#1|' . $context, 1);
};

// Exhausts the chosen unit. 'action' context calls SWUAfterAction; 'attack' does not.
$customDQHandlers["SOR_016#1"] = function($player, $parts, $lastDecision) {
    $context = $parts[0] ?? 'action';
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        if ($context === 'action') SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    OnExhaustCard($player, $lastDecision);
    if ($context === 'action') SWUAfterAction($player);
};

// ── SOR_017 Han Solo "Audacious Smuggler" ───────────────────────────────────

// Leader Action follow-up: put the chosen hand card into play as a READY resource,
// then arm the delayed "defeat a resource you control" trigger for next action phase.
$customDQHandlers["SOR_017#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    SWURampResourceReady(intval($player), $lastDecision);
    AddGlobalEffects(intval($player), 'SWU_HAN_DEFEAT_RESOURCE');
    SWUAfterAction(intval($player));
};

// Deployed leader unit — On Attack: "Put the top card of your deck into play as a
// resource and ready it. At the start of the next action phase, defeat a resource
// you control." Mandatory (no "may"); no player choice. $playerID is already $player.
$onAttackAbilities["SOR_017:0"] = function($player) {
    global $playerID;
    $playerID = intval($player);
    $deck = GetDeck(intval($player));
    $topIdx = null;
    foreach ($deck as $i => $c) {
        if (empty($c->removed ?? false)) { $topIdx = $i; break; }
    }
    if ($topIdx === null) return; // empty deck — nothing to ramp
    SWURampResourceReady(intval($player), "myDeck-" . $topIdx);
    AddGlobalEffects(intval($player), 'SWU_HAN_DEFEAT_RESOURCE');
};

// Resolve the pending "defeat a resource you control" trigger (queued in
// ActionPhaseStart). The player picks one of their resources to defeat.
$customDQHandlers["HAN_DEFEAT_RESOURCE"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        return; // controls no resources, or none chosen — fizzle
    }
    SWUDefeatResource(intval($player), $lastDecision);
};

// ── SOR_018 Jyn Erso ─────────────────────────────────────────────────────────
// Leader-action follow-up: the chosen attacker is tagged with a one-shot SWU_DEF_DEBUFF_1
// (the defender gets -1/-0 for this attack, consumed in SWUCombatDamage), then the attack
// begins. BeginSWUAttack owns the combat continuation / SWUAfterAction.
$customDQHandlers["SOR_018#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, 'SWU_DEF_DEBUFF_1');
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── Reactive defeat / leaves-play triggers (SOR_036 Gideon, SOR_105 Krell, SOR_015 Boba) ──
// SOR_105 General Krell granted "When Defeated: you may draw a card" follow-up.
$customDQHandlers["KRELL_DRAW"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === 'YES') DoDrawCard(intval($player), 1);
};

// SOR_015 Boba Fett (deployed): "When this unit completes an attack: If an enemy unit left play
// this phase, ready up to 2 resources." The SWU_ENEMY_LEFT_PLAY flag is set on Boba's controller
// when an enemy leaves play (incl. the defender Boba just defeated, set before this fires).
$onAttackEndAbilities["SOR_015:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_ENEMY_LEFT_PLAY') > 0) {
        SWUReadyResources(intval($player), 2);
    }
};

// ── Chained "attack with another unit" (SOR_009 Leia, SOR_103 Rebel Assault) ──
// Universal follow-up: apply a one-shot +{bonus}/+0 ("for this attack") to the chosen unit and
// begin its attack. No-op on a '-' decline (the optional "you may attack with another" case).
$customDQHandlers["CHAINED_ATTACK"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $bonus = intval($parts[0] ?? 0);
    if ($bonus > 0) SWUAddAttackPowerBonus($lastDecision, $bonus);
    BeginSWUAttack(intval($player), $lastDecision);
};

// SOR_009 Leia Organa — leader-action follow-up (first attacker chosen): arm the chained "you may
// attack with another Rebel" (rebelOnly, may-decline, +0) keyed to exclude the first attacker, then
// begin the first attack. The ChainedAttack trigger fires the optional second attack after it ends.
$customDQHandlers["SOR_009#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    $uid = ($obj !== null) ? intval($obj->UniqueID ?? 0) : 0;
    SetSWUVar('SWU_CHAINED_ATTACK', "1,1,0,{$uid}"); // rebelOnly, may-decline, +0
    BeginSWUAttack(intval($player), $lastDecision);
};

// SOR_103 Rebel Assault — event follow-up (first attacker chosen): +1/+0 to the first attacker,
// arm the chained MANDATORY "then attack with another Rebel" (+1/+0), then begin the first attack.
$customDQHandlers["SOR_103#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    $uid = ($obj !== null) ? intval($obj->UniqueID ?? 0) : 0;
    SWUAddAttackPowerBonus($lastDecision, 1);
    SetSWUVar('SWU_CHAINED_ATTACK', "1,0,1,{$uid}"); // rebelOnly, mandatory, +1
    BeginSWUAttack(intval($player), $lastDecision);
};

// SOR_009 Leia Organa — Deployed: "When this unit completes an attack: you may attack with another
// Rebel unit." (Her Raid 1 is auto-wired via $Raid_Cards.) Fires after she completes any attack.
$onAttackEndAbilities["SOR_009:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    $uid  = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    SWUQueueAnotherAttack(intval($player), true, true, 0, $uid); // Rebel-only, may decline, +0
};

// SOR_146 Zeb Orrelios — "When this unit completes an attack: If the defender was defeated, you may
// deal 4 damage to a ground unit." The "if defeated" condition is gated at trigger collection
// (CollectAfterAttackTriggers), so reaching here means the defender died. Any ground unit (friendly
// or enemy, including Zeb himself) is a valid target → one MZMAYCHOOSE → DEAL_UNIT_DAMAGE|4.
$onAttackEndAbilities["SOR_146:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter)
    ));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        'Deal_4_damage_to_a_ground_unit', 'Choose_a_ground_unit', 'DEAL_UNIT_DAMAGE|4');
};

// ── SOR_012 IG-88 ────────────────────────────────────────────────────────────
// Leader-action follow-up: if the controller has more units in play than the opponent, the
// chosen attacker gets a one-shot +1/+0 ("for this attack"); then begin the attack.
$customDQHandlers["SOR_012#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $opp = GetOpponent(intval($player));
    if (count(GetUnitsInPlay(intval($player))) > count(GetUnitsInPlay($opp))) {
        SWUAddAttackPowerBonus($lastDecision, 1);
    }
    BeginSWUAttack(intval($player), $lastDecision);
};

// ── SOR_011 Grand Inquisitor ─────────────────────────────────────────────────
// Leader-action follow-up: deal 2 damage to the chosen friendly unit and ready it. The damage
// may defeat it (then there is nothing to ready) — re-resolve by UniqueID and skip if it is gone.
$customDQHandlers["SOR_011#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    $u = GetZoneObject($mz);
    if ($u !== null && empty($u->removed)) OnReadyCard(intval($player), $mz);
};

// Deployed OnAttack: "You may deal 1 damage to another friendly unit with 3 or less power and
// ready it." MZMAYCHOOSE (pick-or-pass) over the other friendly ≤3-power units.
$onAttackAbilities["SOR_011:0"] = function($player) {
    global $playerID;
    $playerID = intval($player);
    $mzID = DecisionQueueController::GetVariable("mzID");
    $self = GetZoneObject($mzID);
    $selfUID = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) use ($selfUID) {
        $o = GetZoneObject($mz);
        return $o !== null && intval($o->UniqueID ?? 0) !== $selfUID && intval(ObjectCurrentPower($o)) <= 3;
    }));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        'Deal_1_damage_to_another_friendly_unit_(3_or_less_power)_and_ready_it',
        'Choose_a_friendly_unit', 'SOR_011#1');
};

// OnAttack follow-up: deal 1 damage to the chosen unit and ready it (no-op on a '-' decline).
$customDQHandlers["SOR_011#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    $u = GetZoneObject($mz);
    if ($u !== null && empty($u->removed)) OnReadyCard(intval($player), $mz);
};

// ── SOR_193 Millennium Falcon "Piece of Junk" ───────────────────────────────

// Shared keep-vs-bounce logic for SOR_193 (both direct resource pay and the
// DROID_PAY FALCON_KEEP continuation path). $paidOk = true if payment succeeded.
function _SWUFalconKeepOrBounce(int $player, string $falconMz, bool $paidOk): void {
    if ($paidOk) {
        AddGameLogEntry('ABILITY', 'P' . $player . ' paid 1 resource to keep Millennium Falcon');
    } else {
        SWUBounceUnit($player, $falconMz);
        AddGameLogEntry('ABILITY', 'P' . $player . ' returned Millennium Falcon to hand');
    }
}

// Regroup "ready cards" trigger: YES = pay 1 resource to keep her on the board;
// NO (or unable to pay) = return her to her owner's hand. $parts[0] = Falcon mzID.
// If SEC_122 Vuutun Palaa is in play and the player has ready Droids, SWUOfferDroidPayment
// queues the central MZMULTICHOOSE + DROID_PAY chain and returns true (Droid branch taken).
// In that case we must NOT restore $playerID — MZCountChoices resolves the MZMULTICHOOSE
// relative mzIDs under $playerID immediately after this handler returns.
$customDQHandlers["SOR_193#0"] = function($player, $parts, $lastDecision) {
    $falconMz = $parts[0] ?? '';
    if ($falconMz === '') return;
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    if (strtoupper((string)$lastDecision) !== 'YES') {
        // Player declined — bounce regardless of SEC_122.
        _SWUFalconKeepOrBounce(intval($player), $falconMz, false);
        $playerID = $savedPID;
        return;
    }

    // Player said YES. Check if the Droid branch will fire before calling, so we
    // know whether to restore $playerID. SWUOfferDroidPayment sets $playerID=$player
    // in the Droid branch and leaves it set; restoring here would break MZCountChoices.
    $droidBranch = (SWUPlayerControlsSEC122(intval($player))
                    && !empty(SWUReadyFriendlyDroids(intval($player))));
    SWUOfferDroidPayment(intval($player), 1, 'FALCON_KEEP', $falconMz, 1);
    if (!$droidBranch) {
        $playerID = $savedPID;
    }
    // Droid branch: $playerID intentionally left = $player (MZCountChoices requirement).
};

// ── Task 1.3: Exploit pre-step resolver ─────────────────────────────────────
// Called after MZMULTICHOOSE "defeat up to X friendly units" for an Exploit card.
// $params: [ mzID-of-card-being-played, grantedExploit-count ].
// $lastDecision: '&'-joined mzIDs of chosen friendly units to defeat, or '-' / '' if none.
$customDQHandlers["EXPLOIT_RESOLVE"] = function($player, $params, $lastDecision) {
    global $gExploitDeferTriggers, $gPlayGrantedExploit, $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    $mzID              = $params[0] ?? '';
    $gPlayGrantedExploit = intval($params[1] ?? 0);   // restore across the request boundary
    $maxDefeats          = intval($params[2] ?? 0);   // effective Exploit X (cap on units defeated)

    $count = 0;
    if ($lastDecision !== null && $lastDecision !== '-' && $lastDecision !== '') {
        // Validate the answer against the SAME friendly-fodder set that was offered, and cap at
        // the effective Exploit X. A non-conforming client must not defeat non-friendly units or
        // exceed the cap. Snapshot each chosen unit's UniqueID BEFORE any defeat mutates arena
        // indices — SWUDefeatUnit → CleanupRemovedCards() splices/re-indexes the arena after each
        // defeat, so a stale mzID would point to the wrong/null slot. Precedent: SWUDealSplitDamage.
        $fodderSet    = array_flip(SWUExploitFodder($player));
        $uidsToDefeat = [];
        foreach (explode("&", $lastDecision) as $chosen) {
            if ($chosen === '') continue;
            if (!isset($fodderSet[$chosen])) continue;          // ignore non-fodder / non-friendly picks
            if (count($uidsToDefeat) >= $maxDefeats) break;     // cap at effective Exploit X
            $o = GetZoneObject($chosen);
            if ($o !== null && empty($o->removed)) $uidsToDefeat[] = intval($o->UniqueID ?? 0);
        }
        $gExploitDeferTriggers = true;                // park WhenDefeated/leave-play/bounty (CR 16.d)
        foreach ($uidsToDefeat as $uid) {
            // Re-resolve current mzID by UID so prior defeats' index shifts don't matter.
            $currentMz = SWUFindMzByUID($uid);
            if ($currentMz === null) continue;
            // Only count units actually defeated — a unit already removed before this
            // handler runs must not inflate the Exploit discount (CR: 2 less per unit defeated).
            if (SWUDefeatUnit(intval($player), $currentMz)) $count++;
        }
        $gExploitDeferTriggers = false;
    }

    // Discount is 2 per unit defeated. SWUContinuePlayAfterExploit → ActivateCard.
    // The event branch of ActivateCard does NOT restore $playerID, so we must not
    // restore it here either — SWUContinuePlayAfterExploit returns with $playerID
    // still set to $player (same as $savedPID), so the restore below is a safe no-op.
    SWUContinuePlayAfterExploit(intval($player), $mzID, 2 * $count);
    $playerID = $savedPID;
};

// ── Task 3.2: LAW_231 Weequay Pirate ────────────────────────────────────────
// "When Played: If no resources were paid to play this unit, give an Experience token to it."
// SWUUnitResourcesPaid reads the SWU_PAID_n TurnEffect stamped by ActivateCard (Task 3.1).
// Returns 0 if no stamp is present (absent = 0 resources paid).
$whenPlayedAbilities["LAW_231:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    if ($obj === null || !empty($obj->removed)) return;
    if (SWUUnitResourcesPaid($obj) === 0) {
        DoGiveExperienceToken(intval($player), $mzID);
    }
};

// TWI_115 Baktoid Spider Droid (Osi Sobeck / Warden of the Citadel) — Exploit 3 (auto-wired)
// When Played: This unit captures an enemy non-leader ground unit with cost equal to or less
// than the number of resources paid to play this unit.
//
// Resources-paid is read from the SWU_PAID_n TurnEffect stamped by ActivateCard.
// With Exploit: defeating 1 unit → paid = 4; defeating 2 → paid = 2; defeating 3 → paid = 0.
// Paid = 0 → nothing eligible (cost ≤ 0 means printed cost 0, which no ground units have in
//             the current card pool), so the ability fizzles.
//
// Target selection: mandatory SWUQueueChooseTarget (PASSPARAMETER if 1 eligible, MZCHOOSE if
// 2+), then TWI_115|{selfUID} re-resolves the captor by UID (arena indices can shift
// during the async queue), calls the CaptureUnit macro.
$whenPlayedAbilities["TWI_115:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);

    $obj = GetZoneObject($mzID);
    if ($obj === null || !empty($obj->removed)) return;

    $paid    = SWUUnitResourcesPaid($obj);
    $selfUID = intval($obj->UniqueID ?? 0);

    // Collect eligible enemy non-leader ground units: printed cost ≤ resources paid.
    $candidates = ZoneSearch("theirGroundArena", AnyUnitFilter);
    $eligible   = [];
    foreach ($candidates as $emz) {
        $eo = GetZoneObject($emz);
        if ($eo === null || !empty($eo->removed)) continue;
        if (IsLeaderUnit($eo)) continue;                   // non-leader only
        if (intval(CardCost($eo->CardID)) <= $paid) $eligible[] = $emz;
    }

    if (empty($eligible)) return;   // fizzle — no eligible target (paid = 0 or no cheap units)

    SWUQueueChooseTarget(
        intval($player),
        $eligible,
        "Capture_an_enemy_non-leader_ground_unit_(cost_≤_{$paid})",
        "TWI_115#0|{$selfUID}"
    );
};

// SHD_131 / TWI_128 Take Captive — step 1: friendly capturer chosen ($lastDecision).
// Carry the capturer's UniqueID in the handler key so step 2 can re-resolve it.
$customDQHandlers["SHD_131#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);

    $captorObj = GetZoneObject($lastDecision);
    if ($captorObj === null || !empty($captorObj->removed)) return;
    $captorUID = intval($captorObj->UniqueID ?? -1);

    // Determine the capturer's arena from its Location ("GroundArena" or "SpaceArena").
    $location = $captorObj->Location ?? 'GroundArena';
    $isSpace  = strpos($location, 'Space') !== false;
    $enemyZone = $isSpace ? 'theirSpaceArena' : 'theirGroundArena';

    // Eligible targets: enemy non-leader units in that same arena.
    $targets = array_values(array_filter(
        ZoneSearch($enemyZone, NonLeaderUnitFilter),
        function($emz) { $eo = GetZoneObject($emz); return $eo !== null && empty($eo->removed); }
    ));
    if (empty($targets)) return;   // fizzle — no valid target in that arena

    SWUQueueChooseTarget(intval($player), $targets,
        'Choose_an_enemy_non-leader_unit_to_capture', 'SHD_131#1|' . $captorUID, 0);
};

// ── TWI_210 Cunning — "When an opponent plays a card: if that opponent paid less than the
// card's cost to play it, ready or exhaust a unit." (Task 5.1)
//
// Step 1: TWI_210#1 — OPTIONCHOOSE result is "Ready" or "Exhaust".
//   $parts contains the unit mzID list (pipe-split from the CUSTOM key via "TWI_210#1|mzA&mzB…").
//   $lastDecision = the chosen mode.
//   Queues MZCHOOSE over the provided unit list, then READY_UNIT or EXHAUST_UNIT.
$customDQHandlers["TWI_210#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);

    // $parts[0] is the '&'-joined unit list passed after the '|' in "TWI_210#1|mzA&mzB&…".
    $unitList = array_values(array_filter(
        explode('&', $parts[0] ?? ''),
        fn($mz) => $mz !== '' && ($o = GetZoneObject($mz)) !== null && empty($o->removed ?? false)
    ));
    if (empty($unitList)) return;   // all units left play before the choice resolved

    $mode    = $lastDecision; // "Ready" or "Exhaust"
    $handler = ($mode === 'Ready') ? 'READY_UNIT' : 'EXHAUST_UNIT';
    $tooltip = ($mode === 'Ready') ? 'Choose_a_unit_to_ready' : 'Choose_a_unit_to_exhaust';
    SWUQueueChooseTarget(intval($player), $unitList, $tooltip, $handler);
};

// SHD_131 / TWI_128 Take Captive — step 2: perform the capture.
// $parts[0] = capturer's UniqueID; $lastDecision = chosen captive's mzID.
$customDQHandlers["SHD_131#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);

    $captorUID = intval($parts[0] ?? -1);
    $captorMzID = SWUFindMzByUID($captorUID);
    if ($captorMzID === null) return;   // capturer left play before capture resolved

    CaptureUnit(intval($player), $captorMzID, $lastDecision);
};

// TWI_115 step 2: perform the capture. $parts[0] = TWI_115's UniqueID (captured before the
// async queue so the mzID is re-resolved by UID after possible arena reindexing).
$customDQHandlers["TWI_115#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);

    $selfUID    = intval($parts[0] ?? 0);
    $captorMzID = SWUFindMzByUID($selfUID);

    if ($captorMzID === null) return;   // TWI_115 left play before capture resolved

    CaptureUnit(intval($player), $captorMzID, $lastDecision);
};

// ── DROID_PAY — central SEC_122 Droid alt-pay resolver ──────────────────────
// "Each friendly Droid unit may be exhausted to pay costs as if it were a resource."
// Queued by SWUOfferDroidPayment for every site (card plays, upgrades, Falcon regroup).
//
// Param encoding: $parts[0] = continuation name (PLAY_CARD | ATTACH_UPGRADE | FALCON_KEEP),
// implode("|", array_slice($parts, 1)) = args string passed to the continuation.
// $lastDecision = "&"-joined mzIDs of Droids the player chose to exhaust, or "-" for none.
//
// VALIDATION: re-derive the ready-Droid set (live, not snapshot) and accept only valid
// choices. Cap at min(|fodder|, MAX_DROIDS_OFFERED) — MAX_DROIDS_OFFERED is re-derived
// from the live ready set, which is conservative (may have shrunk since the offer).
// Exhausting a Droid flips Status to 0 but does not splice arena arrays, so no UID
// snapshot is needed.
//
// After exhausting, delegates to SWUDispatchDroidContinuation($player, $continuation, $args, $prepaid).
// $playerID handling: set to $player throughout. For PLAY_CARD the event branch of
// ActivateCard does NOT restore $playerID, so $savedPID restore is a safe no-op.
// For ATTACH_UPGRADE and FALCON_KEEP the downstream functions restore correctly.
$customDQHandlers["DROID_PAY"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    $continuation = $parts[0] ?? '';
    $args         = implode('|', array_slice($parts, 1)); // rejoin remaining parts as args

    // Validate the player's Droid picks against the live ready set.
    $prepaid = 0;
    if ($lastDecision !== null && $lastDecision !== '-' && $lastDecision !== '') {
        $fodderSet  = array_flip(SWUReadyFriendlyDroids(intval($player)));
        $maxExhaust = count($fodderSet); // cap: cannot exhaust more than exist and are ready
        foreach (explode('&', $lastDecision) as $chosen) {
            if ($chosen === '') continue;
            if (!isset($fodderSet[$chosen])) continue;      // not in offered set / no longer ready
            if ($prepaid >= $maxExhaust) break;             // cap reached
            $o = GetZoneObject($chosen);
            if ($o === null || !empty($o->removed)) continue;
            if (intval($o->Status) !== 1) continue;         // redundant safety check
            OnExhaustCard(intval($player), $chosen);
            $prepaid++;
        }
    }

    // Dispatch to the named continuation with the exhaustion count.
    // PLAY_CARD: ActivateCard's event branch does not restore $playerID, so $savedPID
    // restore below is a safe no-op (mirrors EXPLOIT_RESOLVE's pattern).
    SWUDispatchDroidContinuation(intval($player), $continuation, $args, $prepaid);
    $playerID = $savedPID;
};
