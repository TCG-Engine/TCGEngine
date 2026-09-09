<?php
// HMW_050
// Cost 2 - Low Altitude Combat - [Command,Cunning] - Event - Traits: Tactic - NON-unique
// Text: Move a space unit to the ground arena (it's now a ground unit). If you do, you may attack with
//       a ground unit. It gets +2/+0 for this attack.

// ─── HMW_050 Low Altitude Combat ─────────────────────────────────────────────
// Three clauses, and the middle one is chained to the first:
//   1. "Move a space unit to the ground arena"   — MANDATORY, and UNQUALIFIED
//   2. "IF YOU DO, you may attack with a ground unit"  — optional, and gated on 1 happening
//   3. "It gets +2/+0 for this attack"           — on the ATTACKER, not the traveller
//
// ⚠⚠ "A SPACE UNIT" IS UNQUALIFIED — no "friendly", no "you control" — so the pool is the WHOLE TABLE
// and dragging an ENEMY blocker down out of the space arena is a legal play. This is the documented
// recurring shape (unqualified target words span both sides), and it is invisible without an offer
// assertion: on the ordinary board with one friendly space unit, a friendly-only pool auto-resolves
// onto exactly the same unit a both-sides pool would. Guarded by
// Offer_SpacePool_SpansBothSidesAndExcludesGroundUnits and by
// MoveAnEnemySpaceUnitDown_ItLandsInTheirGroundArena.
// ⚠ HMW is a preview set, so there is no ruling to check; this reads the printed text against the
// released cards that DO qualify (SHD_230 Swoop Down attacks WITH the space unit and so is implicitly
// self-scoped; JTL_096 Blue Leader says "move THIS unit"). Flag it if the ruling lands otherwise.
//
// ⚠ THE MOVED UNIT IS ITSELF A LEGAL ATTACKER. Moving does not exhaust, and by the time clause 2 builds
// its pool the traveller is a ground unit — so a ready space unit can come down and swing in the same
// action. That means the attacker pool must be built AFTER the move, which is why it lives in the
// continuation below rather than being collected up front.
//
// ⚠ "A ground unit" in clause 2 carries no printed qualifier either, but attacking supplies its own:
// you attack with a unit YOU CONTROL, and it must be READY. So this pool is 'my' + Ground + Status 1 —
// deliberately narrower than clause 1's, and the difference between the two is printed, not incidental.

$whenPlayedAbilities["HMW_050:0"] = function ($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // side defaults to 'any' = the whole table (team + every live opponent), so this is already the
    // Twin Suns / Team Suns fan-out; there is no seat loop to hand-roll.
    $space = _SWUCollectUnitTargets(intval($player), ['arena' => 'Space']);
    // No space unit anywhere → nothing moves, and "If you do" means the attack is never offered either.
    // Guarded by NoSpaceUnitAnywhere_NoMove_AndNoAttackIsOffered, which is the section that reds if the
    // two clauses are queued independently.
    if (empty($space)) return;
    SWUQueueChooseTarget(intval($player), $space,
        "Move_a_space_unit_to_the_ground_arena", "HMW_050#0");
};

// Clause 1 resolved → do the move, then chain clause 2 off it.
$customDQHandlers["HMW_050#0"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // mandatory, but a vanished pool must not crash
    if (SWUObjGone(GetZoneObject($lastDecision))) return;

    // SWUMoveUnitBetweenArenas re-creates the unit in its OWNER's ground arena carrying its UniqueID,
    // damage, status, upgrades and turn effects — so the traveller is the same unit in a new arena, not
    // a fresh copy (TheMovedUnitKeepsItsDamageUpgradesAndExhaustedState). It returns '' if the move
    // could not happen, which is exactly the "If you do" failure.
    $newMz = SWUMoveUnitBetweenArenas($lastDecision, 'GroundArena');
    if ($newMz === '') return;

    // ⚠ POOL BUILT AFTER THE MOVE. The unit that just came down is a ground unit now and belongs in it
    // (when it is friendly and ready); a moved ENEMY unit correctly does not, because 'my' excludes it.
    $playerID = intval($player);
    $attackers = _SWUCollectUnitTargets(intval($player), [
        'side' => 'my', 'arena' => 'Ground',
        'extraFilter' => fn($u) => intval($u->Status ?? 0) === 1,   // 1 = ready; you cannot attack exhausted
    ]);
    if (empty($attackers)) return;   // the move still stands; there is simply nobody to swing

    // "You MAY attack" — a real decline branch, and it must not undo the move.
    SWUQueueMayChooseTarget(intval($player), $attackers,
        "Attack_with_a_ground_unit_(+2/+0)?", "Choose_a_ground_unit_to_attack_with", "HMW_050#1");
};

// Clause 2 + 3: the chosen attacker swings with +2/+0 for THIS attack.
$customDQHandlers["HMW_050#1"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    // SWUAddAttackPowerBonus is the ATTACK-duration form (SWU_ATK_POWER_N, consumed by the attack).
    // ⚠ Not SWUApplyPhaseBuff: the text says "for this attack", and a phase buff would still be there
    // for the next one (TheBonusIsForTHISAttackOnly_NotThePhase), and its HP half would keep the
    // attacker alive through a trade it should lose (TheBonusIsPowerOnly_TheAttackerStillDiesToFour).
    SWUAddAttackPowerBonus($lastDecision, 2);
    // BeginSWUAttack takes ownership of closing the action when it detects the event's pending
    // FINISH_PLAY_CARD terminator, so the turn passes exactly once whether the attack is taken or
    // declined — asserted from both directions by the two TURNPLAYER sections.
    BeginSWUAttack(intval($player), $lastDecision);
};
