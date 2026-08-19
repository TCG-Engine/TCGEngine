<?php
// HMW_037
// Cost 1 - Bacta Tank - [Vigilance,Command] - Upgrade - Trait: Fortification
// Text: Fortify (Attach this to your base, not a unit.)
//       When Played: Heal up to 3 damage from a non-Vehicle unit.
//       Action [defeat this upgrade]: Put a non-Vehicle unit from your discard pile on top of your deck.
//
// FORTIFY needs no code — HMW_037 is in $Fortify_Cards (generator-derived) and SWUGetUpgradeValidTargets
// already routes a Fortify upgrade to ['myBase-0'].

// "a non-Vehicle unit" carries NO friendly/enemy qualifier, so BOTH sides are legal — only the Vehicle
// trait excludes. Identical phrasing to HMW_095 Carbonite Chamber, and read the same way.
function _SWUHmw037NonVehicleUnits(int $player): array {
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (TraitContains($o, 'Vehicle')) continue;   // object-aware: honours granted/lost traits
            $out[] = $mz;
        }
    }
    return $out;
}

// ── When Played: heal up to 3 ─────────────────────────────────────────────────────────────────────
// USER RULING (2026-08-14, applied to every "up to N"): the TARGET choice is MANDATORY and the soft pass
// is choosing an amount of ZERO. So a plain MZCHOOSE here, and Heal0 is ALWAYS among the amounts below —
// never a declinable target, and the amount step is never skipped (skipping it when only one point was
// healable once FORCED a heal on a player who did not want it).
$whenPlayedAbilities["HMW_037:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = _SWUHmw037NonVehicleUnits(intval($player));
    if (empty($targets)) return;   // clean fizzle; the play's own FINISH_PLAY_CARD closes the action
    SWUQueueChooseTarget(intval($player), $targets,
        "Heal_up_to_3_damage_from_a_non-Vehicle_unit", "HMW_037#0");
};

// The chosen unit rides to the next request as a UniqueID, never a positional mzID: the amount is picked
// in a SEPARATE request and the arena can reindex in between.
$customDQHandlers["HMW_037#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Heal0&Heal1&Heal2&Heal3", 1,
        tooltip: "Heal_up_to_3_damage");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_037#1|{$uid}", 1);
};

$customDQHandlers["HMW_037#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $amt = ($lastDecision === 'Heal3') ? 3
         : (($lastDecision === 'Heal2') ? 2 : (($lastDecision === 'Heal1') ? 1 : 0));
    if ($amt <= 0 || $uid <= 0) return;                 // Heal0 is a legitimate answer, not a decline
    $mz = SWUFindMzByUID($uid);                          // re-resolve: mzIDs shift across the boundary
    if ($mz !== null) OnHealUnit(intval($player), $mz, $amt);   // clamps at 0 and fires the heal anim
};

// ── Action [defeat this upgrade] ──────────────────────────────────────────────────────────────────
// Base-hosted activated ability: $baseUpgradeAbilities is keyed by the UPGRADE's CardID and reached by
// clicking the base (the seam HMW_095 Carbonite Chamber introduced). It does not touch the base's Epic.
$baseUpgradeAbilities["HMW_037"] = function(int $player, int $upgradeIndex): void {
    global $playerID; $playerID = $player;

    // Pay the cost first — "defeat this upgrade" changes the game state, so the Action stays available
    // even with nothing to fetch: the cost is paid and the effect fizzles (Action_NoLegalTarget_
    // StillPaysTheCost). Same replacement-eligible defeat HMW_095 uses (HMW_060 Rampart may replace it).
    SWUDefeatUpgrade($player, 'myBase-0', $upgradeIndex);

    // "a non-Vehicle UNIT from YOUR discard pile" — three filters: units only (an event or upgrade in the
    // pile is not a legal pick), non-Vehicle, and the CONTROLLER's own pile (seat-scoped, unlike the
    // heal's unqualified target above). Trait is read from the CardID: a discarded card is not in play,
    // so per-instance trait grants/losses cannot apply to it.
    $targets = [];
    foreach (ZoneSearch("myDiscard", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (HasTrait($o->CardID ?? '', 'Vehicle')) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets,
        "Put_a_non-Vehicle_unit_from_your_discard_on_top_of_your_deck", "HMW_037#2");
};

$customDQHandlers["HMW_037#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        $o = GetZoneObject((string)$lastDecision);
        if (!SWUObjGone($o)) {
            $cid = (string)($o->CardID ?? '');
            $o->removed = true;
            DecisionQueueController::CleanupRemovedCards();
            // TOP of the deck is index 0 — the engine's deck-top convention everywhere (DoDrawCard,
            // DoScry, the look-at-top family, and WithP{n}Deck's first entry). Reindex after the
            // unshift so every mzIndex still matches its slot.
            $deck = &GetDeck(intval($player));
            array_unshift($deck, new Deck($cid, 'Deck', intval($player)));
            foreach ($deck as $i => $c) { $c->mzIndex = $i; }
        }
    }
    SWUAfterAction(intval($player));
};
