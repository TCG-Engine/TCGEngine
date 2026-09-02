<?php
// HMW_207
// Cost 1 - Maim - [Cunning][Villainy] - Event - Traits: Tactic
// Text: Deal 1 damage to a unit and exhaust it.
//
// The direct sibling is JTL_230 Electromagnetic Pulse ("Deal 2 damage to a Droid or Vehicle unit and
// exhaust it") — same sentence shape, same two-step choose-then-resolve. Maim differs in exactly two
// ways, and both are in the printed text:
//   • the amount is 1, not 2;
//   • the target set is UNQUALIFIED. "a unit" names no controller, no arena and no trait, so the pool
//     is every unit on the table — friendly AND enemy, ground AND space, token units and deployed
//     leader units included. SWUAllUnits() with no arguments is exactly that pool (it uses
//     AnyUnitFilter = ['Unit','Token Unit','Leader Unit'], and starts from 'team' so it stays correct
//     in a Team Suns game). Do NOT narrow it to theirGroundArena/theirSpaceArena.
//
// ⚠ PREVIEW SET — HMW is not in card-specific-rulings.md, so one reading is an assumption, flagged
// here and pinned by ShieldedTarget_DamagePrevented_StillExhausted: the two halves are joined by
// "and", NOT by "If you do", so the exhaust is UNCONDITIONAL. A Shield token that absorbs the whole
// 1 damage does not stop the unit being exhausted. Same reading as HMW_202 Inferno Squad's
// "deal damage ... and give a Weakness token".
//
// Mandatory, not optional: the text carries no "you may" and no "up to", so the target choose is a
// plain MZCHOOSE (SWUQueueChooseTarget) and the player is committed once the event resolves.
//
// Format triage: no player reference and no friendly/enemy wording, so Premier, Twin Suns and Team
// Suns all share this one code path — 2P coverage is complete for this card.

// ── HMW_207 Maim (event continuation) — deal 1 to the chosen unit, then exhaust it. ─────────────────
$customDQHandlers["HMW_207#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o   = GetZoneObject($lastDecision);
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    // Re-resolve by UniqueID rather than reusing $lastDecision: the damage may have DEFEATED the
    // target (CleanupRemovedCards then compacts the arena, so the chosen positional mzID would name a
    // survivor that shifted down into the slot). A null result means the unit left play and the
    // exhaust is moot. Passing the CASTER as the actor is what lets OnExhaustCard apply the
    // "can't be exhausted by enemy card abilities" immunity for an enemy target and skip it for a
    // friendly one.
    if ($uid !== 0) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') OnExhaustCard(intval($player), $mz);
    }
};

// When Played (event).
$whenPlayedAbilities["HMW_207:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $targets  = SWUAllUnits();   // unqualified "a unit" — see the header note.
    // No `if (empty($targets)) return;` here on purpose: SWUQueueChooseTarget already returns on an
    // empty pool (GameLogic.php:1546), so with no unit anywhere the event simply plays, pays, hits the
    // discard and resolves to nothing — no dangling decision. MEASURED, not assumed: deleting a local
    // guard changed nothing in the suite, which is the "genuinely redundant code" case, so it is gone
    // rather than kept with a justification the next card would copy. Pinned by
    // NoUnitsInPlay_EventFizzlesCleanly_NoDanglingDecision.
    SWUQueueChooseTarget(intval($player), $targets,
        "Deal_1_damage_to_a_unit_and_exhaust_it", "HMW_207#0");
};
