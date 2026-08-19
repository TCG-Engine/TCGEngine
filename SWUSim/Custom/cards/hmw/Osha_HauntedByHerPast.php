<?php
// HMW_017
// Cost 6 - Osha - Haunted by her Past - [Cunning,Heroism] - LEADER (Ground) 5/6 - Trait: Force
// FRONT:    Action [Exhaust]: If a friendly Heroism unit was defeated this phase, play a Villainy unit
//           from your resources, ignoring its Villainy aspect penalties. If you do so, you may resource
//           a card from your hand.
//           Epic Action: If you control 6 or more resources, deploy this leader.
// DEPLOYED: Saboteur
//           Action: Play a Villainy unit from your resources, ignoring its Villainy aspect penalties.
//           If you do, you may resource a card from your hand.

// ─── HMW_017 Osha ──────────────────────────────────────────────────────────────────────────────────
// The two sides share the PLAY, and differ in exactly two things — both of which live in the callers
// below, never in the shared machinery, so the deployed side cannot inherit the front's restrictions:
//   • the front is gated on "a friendly Heroism unit was defeated this phase"; the deployed side is not;
//   • the front costs [Exhaust]; the deployed side prints no cost bracket at all.
// Deployed Saboteur needs no wiring — the keyword generator derives it from deployTextData.

// The legal, AFFORDABLE "Villainy unit" resources, as myResources-N in zone-position order.
function _SWUOsha017Targets(int $player): array {
    global $playerID; $playerID = intval($player);
    $cap = SWUTotalPaymentCapacity($player);      // ready resources + Credits + SEC_122 Droids
    $res = &GetResources($player);
    $out = []; $pos = 0;
    for ($i = 0; $i < count($res); $i++) {
        if (!empty($res[$i]->removed)) continue;
        $here = $pos; $pos++;                     // mzIDs index the COMPACTED zone, not the raw array
        $cid = (string)($res[$i]->CardID ?? '');
        if ($cid === '') continue;
        // "a VILLAINY UNIT" is two filters. Token Unit passes the type check ("Unit" is a substring);
        // an Event or an Upgrade does not, however Villainy it is.
        if (strpos((string)(CardType($cid)   ?? ''), 'Unit')    === false) continue;
        if (strpos((string)(CardAspect($cid) ?? ''), 'Villainy') === false) continue;
        if (SWUCardPlayBlocked($player, $cid)) continue;
        // Price it with the waiver ACTIVE, or the offer and the payment would disagree about what is
        // affordable — the one-function-for-both-sides rule that keeps a glow from lying.
        $GLOBALS['gOsha017IgnoreVillainy'] = true;
        $cost = SWUComputePlayCost($player, $res[$i]);
        $GLOBALS['gOsha017IgnoreVillainy'] = false;
        // A card cannot pay for itself out of the resource zone, but an EXHAUSTED one never counted
        // toward capacity in the first place, so only a READY slot is subtracted (bug #955, ASH_001).
        $selfReady = (intval($res[$i]->Status ?? 0) === 1) ? 1 : 0;
        if ($cost > $cap - $selfReady) continue;
        $out[] = "myResources-{$here}";
    }
    return $out;
}

// Shared entry for both sides. The caller has already applied its own gate and paid its own cost.
function _SWUOsha017Offer(int $player): void {
    $targets = _SWUOsha017Targets($player);
    if (empty($targets)) { SWUAfterAction($player); return; }
    // MANDATORY: the text prints no "may", and RESOURCES ARE A PUBLIC ZONE — the hidden-zone ruling that
    // makes every play-from-HAND declinable does not reach a play from resources.
    SWUQueueChooseTarget($player, $targets, "Play_a_Villainy_unit_from_your_resources", "HMW_017#0");
}

// ── Step 0: the resource was chosen — play it, then measure whether it actually entered play ───────
$customDQHandlers["HMW_017#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction($player); return; }
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction($player); return; }
    $before = count(GetUnitsInPlay(intval($player)));
    // Nested play, guarded so the inner ActivateCard's After Action doesn't double-advance this Action
    // (the JTL_089#1 turn/PASS save-restore). The waiver is re-applied HERE because this runs in a
    // different request from the offer above — nothing is carried across the boundary.
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    $GLOBALS['gOsha017IgnoreVillainy'] = true;
    ActivateCard(intval($player), (string)$lastDecision, false);
    $GLOBALS['gOsha017IgnoreVillainy'] = false;
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    // This IS "playing a card from your resources", so the observers of that event fire (SEC_008 Bail).
    _SWUSec008HealOnResourcePlay(intval($player));
    // "If you do so" / "If you do" — MEASURE the outcome rather than assuming the play landed. A play can
    // still fail here (an uncovered cost, a play-block), and the rider must not fire off a failed attempt.
    if (count(GetUnitsInPlay(intval($player))) <= $before) { SWUAfterAction($player); return; }
    $hand = ZoneSearch("myHand");
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueMayChooseTarget(intval($player), $hand,
        "Resource_a_card_from_your_hand?", "Choose_a_card_to_resource", "HMW_017#1");
};

// ── Step 1: the optional resource-from-hand ───────────────────────────────────────────────────────
$customDQHandlers["HMW_017#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        $r = MZMove(intval($player), (string)$lastDecision, "myResources");
        // Status 0 = EXHAUSTED. "Resource a card" with no "and ready it" rider is the ordinary regroup
        // resourcing (contrast TS26_12 Sundari Palace, which says "and ready it" explicitly).
        if ($r !== null) { $r->Status = 0; SWUKeepCreditTokensLast(intval($player)); }
    }
    SWUAfterAction($player);
};

// ── FRONT side ────────────────────────────────────────────────────────────────────────────────────
// SWULeaderAction has already exhausted Osha by the time this runs — [Exhaust] is the COST and is paid
// regardless. The Heroism-death check is an EFFECT CONDITION (it sits after the colon, outside the
// bracket), so it belongs HERE and never in SWULeaderActionAffordable: gating affordability on it would
// make the Action disappear instead of resolving to nothing (the TS26_02 Anakin lesson).
// SWU_FRIENDLY_HEROISM_DEFEATED is the existing per-phase flag _SWUMarkHeroismDefeated stamps beside
// every SWU_FRIENDLY_DEFEATED (built for TWI_017, cleared at RegroupPhaseStart).
$leaderAbilities["HMW_017"] = function($player) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_HEROISM_DEFEATED') <= 0) {
        SWUAfterAction($player);   // soft pass: the leader stays exhausted, nothing else happens
        return;
    }
    _SWUOsha017Offer(intval($player));
};

// ── DEPLOYED side ─────────────────────────────────────────────────────────────────────────────────
// No cost bracket at all on the deployed Action, so cost kind 'none': it must NOT exhaust the leader
// unit and stays usable while exhausted. And NO Heroism-death condition — that is the front's alone.
$unitActionCostKind["HMW_017"] = 'none';
$unitAbilities["HMW_017"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    _SWUOsha017Offer(intval($player));
};
