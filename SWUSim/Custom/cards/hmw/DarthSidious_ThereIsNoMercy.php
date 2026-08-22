<?php
// HMW_011
// Cost 6 - Darth Sidious - There is No Mercy - [Aggression][Villainy] - Leader (Ground) 4/5
// Traits: Force, Sith - Unique
// FRONT:    When you deal 4 or more damage to a unit or a base: You may exhaust this leader.
//           If you do, deal 1 damage to a different unit or base.
// EPIC:     Epic Action: If you control 6 or more resources, deploy this leader.   <- the STANDARD deploy
//           line every leader carries; handled generically by the engine, no code here.
// DEPLOYED: Hidden (This unit can't be attacked if it was played this phase.)
//           When you deal 4 or more damage to a unit or a base: You may deal 1 damage to a different
//           unit or base.
//
// HIDDEN needs no code — HMW_011 is already in $Hidden_Cards (derived from the deploy text).
//
// ── THE FIRST "WHEN YOU DEAL N OR MORE DAMAGE" OBSERVER IN THE ENGINE ─────────────────────────────
// Both sides carry the SAME trigger and differ only in COST: the front side must exhaust the leader,
// the deployed side is free. One collector serves both; the cost is re-checked when the offer drains.
//
// USER RULINGS (2026-08-21), all three load-bearing and none derivable from the text alone:
//   1. COMBAT DAMAGE COUNTS. The text carries no "with an ability" qualifier, so an attack that deals
//      4+ to a unit triggers it. That is why this is wired into the combat funnel and not just the
//      ability one — the single most common way the trigger fires.
//   2. PER DAMAGE INSTANCE, NOT PER EVENT — "a unit", singular. A divided "deal 4 damage among any
//      number of units" that lands 2+2 does NOT trigger; each unit took 2. Conversely ONE attack can
//      fire it TWICE: a 5-power Overwhelm attacker into a 1-HP unit deals 5 to that unit (trigger) and
//      4 excess to the base (a second, separate trigger).
//   3. YOUR OWN CARDS COUNT. "A unit or a base" names no controller, so damage you deal to your own
//      board triggers it too — which is what makes ASH_151 Operation Cinder ("deal 5 to your base,
//      then 5 to each unit") a Sidious engine: one trigger for the base and one per unit on the table.
//
// ⚠ SIDIOUS OBSERVES A BATCH THAT KILLS HIM. Operation Cinder's 5 defeats a deployed Sidious (4/5)
//   partway through its own loop, and every later unit's damage is still dealt while he was in play —
//   the same-batch observer family (a live count taken after the fact misses an observer that traded).
//   _SWUHmw011Mode therefore accepts a leader-unit object still sitting in the arena marked `removed`,
//   i.e. defeated earlier in THIS uninterrupted resolution but not yet compacted away by
//   CleanupRemovedCards. A Sidious defeated in an earlier action is long gone from the array, so this
//   cannot produce a false positive across actions.
//
// ⚠ NO DEDUPE, deliberately — unlike JTL_009 Boba Fett's SWU_BOBA_009_PENDING guard, which collapses a
//   multi-target hit into one offer. Sidious is explicitly meant to fire once PER qualifying instance
//   (ruling 3). The front side self-limits instead: it costs an exhaust, so the second offer finds an
//   exhausted leader and declines itself at drain time.

// Which side of Sidious can pay for the reaction right now: 'deployed' (free), 'front' (exhaust a ready
// undeployed leader), or null (no Sidious, or the front side is already exhausted).
// ⚠ Re-evaluated when the offer DRAINS, not when it is collected: one damage batch can queue several
//   offers before any is answered, and the front side can only ever pay for one of them.
if (!function_exists('_SWUHmw011Mode')) {
    function _SWUHmw011Mode(int $seat): ?string {
        global $playerID;
        $saved = $playerID; $playerID = intval($seat);
        $deployed = false;
        foreach (['myGroundArena', 'mySpaceArena'] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                // NOTE: `removed` objects are accepted on purpose — see the same-batch note above.
                if ($o === null) continue;
                if (($o->CardID ?? '') !== 'HMW_011') continue;
                if (!IsLeaderUnit($o)) continue;
                $deployed = true; break 2;
            }
        }
        $playerID = $saved;
        // ⚠ THE OBSERVATION WINDOW. A deployed Sidious defeated mid-batch does NOT linger in the arena
        // the way a normal unit does — SWUDefeatUnit returns a leader unit to the LEADER zone and flips
        // Deployed off — so the loop above finds nothing for every later instance of the same damage
        // event, and ASH_151 Operation Cinder (5 to your base, then 5 to EACH unit) would fire once
        // instead of once per instance. Stamping the first sighting keeps him observing for the rest of
        // the ACTION; SWUAfterAction clears it, so a Sidious who died in an earlier action never
        // observes a later one. Request-scoped on purpose: the batch it covers is synchronous, and the
        // MODE each offer resolves under is already baked into its CUSTOM param at collect time.
        if ($deployed) {
            $GLOBALS['gHmw011DeployedThisAction'][intval($seat)] = true;
            return 'deployed';
        }
        if (!empty($GLOBALS['gHmw011DeployedThisAction'][intval($seat)])) return 'deployed';
        return _SWULeaderReadyUndeployed(intval($seat), 'HMW_011') ? 'front' : null;
    }
}

// THE COLLECTOR — called from every damage funnel. $damagedRef identifies what just took the damage so
// the follow-up can enforce "a DIFFERENT unit or base": "U<uniqueID>" for a unit, "B<seat>" for a base.
if (!function_exists('_SWUCollectHmw011Threshold')) {
    function _SWUCollectHmw011Threshold(int $dealer, int $amount, string $damagedRef): void {
        if ($dealer <= 0 || $amount < 4) return;              // the threshold, per INSTANCE
        if (_SWUHmw011Mode($dealer) === null) return;         // no Sidious able to react
        // Queued rather than offered inline: this fires mid-combat and mid-loop, BEFORE
        // CleanupRemovedCards compacts the arenas, so a pool built now would carry positional mzIDs that
        // go stale before the player answers (the SEC_143 shape). The CUSTOM drains post-cleanup.
        // ⚠ The MODE is decided HERE, at collect time, and RIDES the param. It cannot be recomputed when
        // the offer drains: by then CleanupRemovedCards has compacted away a Sidious that the very same
        // damage batch defeated, and every trigger he legitimately saw would fizzle (the whole ASH_151
        // Operation Cinder line). The trigger has already happened; the ability does not need its source
        // to resolve. What IS re-checked at drain time is only the front side's exhaust — see below.
        $mode = _SWUHmw011Mode($dealer);
        DecisionQueueController::AddDecision($dealer, "CUSTOM", "HMW_011#OFFER|{$damagedRef}|{$mode}", 1);
    }
}

// Build the offer against the LIVE board. "A different unit or base" is enforced by IDENTITY (UID for a
// unit, owner seat for a base) rather than by position, which a mid-batch defeat would have shifted.
$customDQHandlers["HMW_011#OFFER"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $seat = intval($player);
    $playerID = $seat;
    $mode = (string)($parts[1] ?? '');
    // Only the FRONT side can become unpayable between collect and drain: one damage batch can queue
    // several offers and a single leader can be exhausted for only one of them. The deployed side is
    // free, so its offers stand even if Sidious has since been defeated (see the collector's note).
    if ($mode === 'front' && !_SWULeaderReadyUndeployed($seat, 'HMW_011')) return;
    if ($mode !== 'front' && $mode !== 'deployed') return;
    $ref = (string)($parts[0] ?? '');
    $exclUID  = (strlen($ref) > 1 && $ref[0] === 'U') ? intval(substr($ref, 1)) : 0;
    $exclBase = (strlen($ref) > 1 && $ref[0] === 'B') ? intval(substr($ref, 1)) : 0;

    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            if ($exclUID > 0 && intval($o->UniqueID ?? -1) === $exclUID) continue;   // the damaged unit
            $targets[] = $mz;
        }
    }
    foreach (SWUAllBaseMzIDs($seat, 'any') as $bmz) {
        if ($exclBase > 0 && SWUMzOwner($bmz, $seat) === $exclBase) continue;               // the damaged base
        $targets[] = $bmz;
    }
    // An optional effect with nothing to hit is never offered: answering it could only waste the front
    // side's exhaust for no effect (the fizzle-only-optional rule).
    if (empty($targets)) return;

    SWUQueueMayChooseTarget($seat, $targets,
        "Deal_1_damage_to_a_different_unit_or_base?",
        "Deal_1_damage_to_a_different_unit_or_base",
        "HMW_011#PING|{$mode}");
};

// Pay (front side only) and deal the 1. The mode is read AGAIN here: the offer and the answer are
// separated by a request boundary, and the leader may have been exhausted or defeated in between.
$customDQHandlers["HMW_011#PING"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $seat = intval($player);
    $playerID = $seat;
    if (SWUDecisionDeclined($lastDecision)) return;
    // The mode rides through the may-choose too: re-deriving it here would again lose a Sidious the
    // same batch defeated. $parts[0] is set by the offer handler when it queues the choose.
    $mode = (string)($parts[0] ?? '');
    if ($mode !== 'front' && $mode !== 'deployed') return;
    if ($mode === 'front') {
        // "You may exhaust this leader. If you do, …" — the exhaust IS the cost, so a leader that can no
        // longer be exhausted deals nothing. Uses the shared helper (the JTL_009 Boba Fett idiom) rather
        // than writing Ready by hand, so the two cards' notion of "exhaust the front side" cannot drift.
        if (!_SWULeaderReadyUndeployed($seat, 'HMW_011')) return;
        _SWUExhaustUndeployedLeader($seat, 'HMW_011');
    }
    $target = (string)$lastDecision;
    if ($target === '') return;
    if (strpos($target, 'Base') !== false) {
        SWUDealDamageToBase(1, SWUMzOwner($target, $seat), $seat);
    } else {
        SWUDealDamageToUnit($target, 1, $seat);
    }
};
