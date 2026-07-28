<?php
// SOR_011
// Cost 6 - Grand Inquisitor - Hunting the Jedi - [Aggression,Villainy] - Power 3 - HP 6
// Text: Action [exhaust]: Deal 2 damage to a friendly unit with 3 or less power and ready it.
// DeployText: On Attack: You may deal 1 damage to another friendly unit with 3 or less power and ready it.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── SOR_011 Grand Inquisitor ─────────────────────────────────────────────────
// Leader-action follow-up: deal 2 damage to the chosen friendly unit and ready it. The damage
// may defeat it (then there is nothing to ready) — re-resolve by UniqueID and skip if it is gone.
$customDQHandlers["SOR_011#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
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
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    $u = GetZoneObject($mz);
    if ($u !== null && empty($u->removed)) OnReadyCard(intval($player), $mz);
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
