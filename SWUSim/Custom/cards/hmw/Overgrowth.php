<?php
// HMW_151
// Cost 5 - Overgrowth - [Command] - Event - Trait: Disaster
// Text: If you control a Kashyyyk base, a friendly unit deals damage equal to its power to an enemy
//       unit. Resource this card.

// ── HMW_151 step 1: the friendly DEALER was chosen; now pick the enemy target ──────────────────────
// The dealer's mzID rides the handler PARAM, never a global: this chain crosses a request boundary
// (production starts a fresh process on every answer), so anything held in memory would be gone.
$customDQHandlers["HMW_151#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Choose_an_enemy_unit", "HMW_151#1|" . $lastDecision, 0);
};

// ── HMW_151 step 2: deal the dealer's CURRENT power to the chosen enemy ────────────────────────────
$customDQHandlers["HMW_151#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $dealer = GetZoneObject($parts[0] ?? '');
    if (SWUObjGone($dealer)) return;
    // CURRENT power (upgrades, auras, tokens), not the printed value.
    $power = intval(ObjectCurrentPower($dealer));
    if ($power > 0) SWUDealDamageToUnit($lastDecision, $power, intval($player));
};

$whenPlayedAbilities["HMW_151:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $me = intval($player);

    // ── Clause 1 — GATED on controlling a Kashyyyk base. Needs both a dealer and a target; with
    // either pool empty the clause simply fizzles (it is mandatory, so no "may" anywhere).
    if (_SWUControlsBaseWithTrait($me, 'Kashyyyk')) {
        $friendly = SWUAllUnits('my');
        $enemy    = SWUAllUnits('their');
        if (!empty($friendly) && !empty($enemy)) {
            SWUQueueChooseTarget($me, $friendly, "Choose_your_unit_to_deal_damage", "HMW_151#0");
        }
    }

    // ── Clause 2 — "Resource this card." A SEPARATE SENTENCE, so it is UNCONDITIONAL: it happens with
    // no Kashyyyk base, with no legal dealer, and with no legal target. Wrapping it inside the gate
    // above is the single most likely way to get this card wrong.
    //
    // The spent event is already sitting in a DISCARD by now (ActivateCard files it before dispatching
    // the When Played), and it goes to its OWNER's pile — which is not the caster's when the card is
    // played from a foreign zone. So search the caster's pile first and fall back to the opponent's,
    // re-framing the zone-relative token for the cross-seat case, and PRESERVE the owner rather than
    // rewriting it to the caster. Same shape as the LAW_171 Stockpile fix.
    //
    // ⚠ This runs INLINE while clause 1's picks are still queued, so the card is resourced before the
    // damage resolves — the reverse of printed order. Not observable: nothing in clause 1 reads the
    // resource zone, and nothing in clause 2 reads the board.
    // Status 0 = EXHAUSTED: a plain "resource this card" has no ready rider (contrast HMW_123).
    $owner = $me;
    $evMz  = _SWUFindDiscardMzID($me, 'HMW_151');
    if ($evMz === null) {
        $opp  = OtherPlayer($me);
        $evMz = _SWUFindDiscardMzID($opp, 'HMW_151');
        if ($evMz !== null) $owner = $opp;
    }
    if ($evMz === null) return;
    $playerID = $me;
    if ($owner !== $me) $evMz = str_replace('myDiscard', 'theirDiscard', $evMz);
    $r = MZMove($me, $evMz, "myResources");
    if ($r !== null) {
        $r->Status     = 0;
        $r->Owner      = $owner;
        $r->Controller = $me;
        SWUKeepCreditTokensLast($me);
    }
};
