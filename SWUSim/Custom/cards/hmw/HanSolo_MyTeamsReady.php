<?php
// HMW_170
// Cost 5 - Han Solo - My Team's Ready - [Aggression,Heroism] - Unit (Ground) 4/7 - Traits: Rebel, Official
// Text: Action [Exhaust]: Ready another unit.

// ─── HMW_170 Han Solo ──────────────────────────────────────────────────────────────────────────────
// [Exhaust] is the printed cost, so the default 'exhaust' cost kind: SWUUnitAction requires Han to be
// READY and taps him before this closure runs — the closure never re-exhausts him.
$unitActionCostKind["HMW_170"] = 'exhaust';

// TARGET SET — "another unit" carries NO controller restriction, so it spans BOTH sides: readying an
// ENEMY unit is legal, if rarely wise (same rule as JTL_088 Phasma's "another First Order unit"). The
// only thing "another" excludes is Han himself, by UniqueID. An already-READY unit stays a legal target
// too — the text says "a unit", not "an exhausted unit" — and READY_UNIT routes through OnReadyCard, so
// a unit that CAN'T ready (SHD_193 Frozen in Carbonite, SOR_186's marker) is correctly left alone
// rather than having its Status written directly (the TS26_63 Rex slip).
//
// MANDATORY: no printed "may", so SWUQueueChooseTarget — the player is committed once they act.
//
// ⚠ With no other unit in play the Action is still USABLE and simply resolves to nothing, exhausting
// Han: [Exhaust] is a COST and is paid regardless. Deliberately NOT gated in SWUUnitActionAffordable —
// that is the TS26_02 Anakin rule (conditions belong in the handler; affordability is about paying the
// cost), and an exhaust-only action is a legal soft pass. Contrast LAW_065, where the fizzle left the
// chosen attacker READY: nothing is corrupted here, the cost is simply spent.
$unitAbilities["HMW_170"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = _SWUCollectUnitTargets(intval($player), [
        'side'       => 'any',
        'excludeUID' => SWUObjUID(GetZoneObject($mzID)),
    ]);
    // No legal target: close the action here, since nothing will be queued to close it.
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Ready_another_unit", "READY_UNIT");
    // READY_UNIT is a shared universal handler and deliberately does not close the action (events use
    // it too), so a unit ACTION appends the terminal itself — the LOF_134/LOF_178 pattern.
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};
