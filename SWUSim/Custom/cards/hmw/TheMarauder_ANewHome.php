<?php
// HMW_125
// Cost 7 - The Marauder, A New Home - [Command,Heroism] - Unit (Space) 5/7
// Traits: Vehicle, Transport - Unique
// Text: While playing this unit, you may choose any number of friendly units. Deal 1 damage to each of
//       them. For each unit chosen this way, this unit costs [1 resource] less.

// ─── HMW_125 The Marauder, A New Home ────────────────────────────────────────
// This is EXPLOIT'S SHAPE with three deliberate differences, and each one is a section:
//   Exploit                         The Marauder
//   "up to N"                  →    "ANY NUMBER" (the cap is the friendly pool, not a printed X, and
//                                   not the cost — over-choosing is legal, just wasteful)
//   defeat the chosen units    →    deal them 1 damage each (often survivable, sometimes lethal)
//   "for each unit DEFEATED"   →    "for each unit CHOSEN this way" — so a pick whose damage is
//                                   PREVENTED by a Shield still buys its 1 resource
// The last one is why this counts picks up front instead of copying EXPLOIT_RESOLVE's count-successful-
// defeats loop: the printed words are different and they mean different things.
//
// PLUMBING is HMW_048 Vernestra Rwoh's, which is Exploit's: _SWUBeginPlayCardUnitPath owns the offer and
// this CUSTOM resolves it, then hands back to SWUContinuePlayAfterExploit — the same funnel every unit
// play uses, so the Credit/Droid alt-payment and the actual cost are charged in one place.
// ⚠ SAME SCOPE AS EXPLOIT: this lives on the SWUBeginPlayCard path, so a direct-ActivateCard nested play
// (SOR_219 Sneak Attack, play-from-deck effects) skips it exactly as those paths already skip Exploit.
// That is a documented engine-family gap (see hmw-implement.md), not a per-card choice.
//
// ⚠ The picker is a 0-minimum multi-select, so it MUST go through SWUQueueMultiChoose: confirming with
// nothing selected submits the literal "PASS", which goes sticky and would make ExecuteStaticMethods
// skip this CUSTOM — and this CUSTOM is what PLAYS THE CARD, so the card would simply vanish from the
// game. SWUQueueMultiChoose derives dontSkipOnPass from min <= 0 so that cannot be forgotten.
// Guarded by ChooseNone_EmptyConfirm_FullPrice, a byte-for-byte twin of the `-` decline section.
//
// "FRIENDLY units" is team-wide, not you-control — a teammate's unit is a legal pick in Team Suns.
// There is no player reference anywhere in the text, so nothing fans out across opponents.

// Legal picks: every FRIENDLY unit in play (either arena, the whole team). The Marauder itself is still
// in HAND while this resolves, so it is never in its own pool.
function _SWUHmw125LegalPicks(int $player): array {
    return _SWUCollectUnitTargets(intval($player), ['side' => 'friendly']);
}

// Resolve the pick: re-validate server-side against the SAME pool that was offered, deal the damage, then
// continue the play with the discount folded in.
// $parts = [handMzID, playDiscountSoFar, "ready~grantTE~shield", offeredMax].
$customDQHandlers["HMW_125#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $handMz   = $parts[0] ?? '';
    $discount = intval($parts[1] ?? 0);
    $snap     = explode('~', (string)($parts[2] ?? ''), 3);
    // The OFFERED maximum, carried across the request boundary. The schema harness (and a
    // non-conforming client) hands an answer straight to this handler without consulting the
    // decision's {max}, so the cap is only real if the server re-applies it here — otherwise a
    // "picked more than were offered" section passes while the live offer was wrong.
    $offeredMax = intval($parts[3] ?? PHP_INT_MAX);

    $uids = [];
    if ($lastDecision !== null && $lastDecision !== '' && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        // Re-check the filter on the server — the offer's pool is a hint, never the gate.
        $legal = array_flip(_SWUHmw125LegalPicks(intval($player)));
        $seen  = [];
        foreach (explode('&', $lastDecision) as $mz) {
            if ($mz === '' || !isset($legal[$mz]) || isset($seen[$mz])) continue;
            $seen[$mz] = true;
            if (count($uids) >= $offeredMax) break;       // never exceed what was actually offered
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $uids[] = intval($o->UniqueID ?? 0);
        }
    }
    // ⚠ ABORT BEFORE ANYTHING IS APPLIED if the picks did not reduce the cost far enough. The card GLOWS
    // at its best-case reduction (CanAffordActivationReserve subtracts the whole friendly pool), so a
    // player holding 5 against a 7-cost Marauder with two friendly units may legally start the play and
    // then confirm only ONE pick — pricing it at 6, which ActivateCard rejects with "Not enough ready
    // resources". Playing a card is atomic, so the 1 damage that pick would have taken must not survive
    // the failed play. Reported 2026-08-28; guarded by UnderChoose_StillUnaffordable_NothingHappens.
    // Checked here rather than reverted later because the damage is not undoable once dealt — a pick it
    // defeats has already fired its When-Defeated.
    if (!_SWUPlayIsPayableAtDiscount(intval($player), $handMz, $discount + count($uids))) {
        SetFlashMessage("Not enough resources to play this even after the reduction — nothing was chosen.");
        return;
    }
    // "Deal 1 damage to each of them" — one ability damaging several units, i.e. SIMULTANEOUSLY (cf. the
    // official Rancor Keeper ruling 07/21/2026). Units are re-resolved by UID because a pick killed by
    // its own 1 compacts the arena underneath the next lookup, and the batch window keeps a defeat
    // observer that is itself a victim able to see its co-victims.
    if (!empty($uids)) {
        SWUSimulDefeatBegin();
        foreach ($uids as $uid) {
            $mzNow = SWUFindMzByUID($uid);
            if ($mzNow !== null) SWUDealDamageToUnit($mzNow, 1, intval($player));
        }
        SWUSimulDefeatEnd();
    }
    // Restore the play-grant globals the original caller had armed (it nulls them once it returns, and
    // the picker sits across a request boundary).
    global $gForceEnterReady, $gPlayGrantTurnEffect, $gPlayGrantShield;
    if (($snap[0] ?? '0') === '1') $gForceEnterReady     = true;
    if (($snap[1] ?? '')  !== '') $gPlayGrantTurnEffect = $snap[1];
    if (intval($snap[2] ?? 0) > 0) $gPlayGrantShield     = intval($snap[2]);
    // ⚠ count($uids) is the number CHOSEN, measured before any damage — a Shielded pick that took
    // nothing still bought its resource. SWUContinuePlayAfterExploit floors the cost at 0, which is what
    // makes over-choosing harmless rather than negative.
    SWUContinuePlayAfterExploit(intval($player), $handMz, $discount + count($uids));
    // $playerID intentionally not restored — the continue funnel's callers own the restore
    // (mirrors EXPLOIT_RESOLVE / HMW_048#0 / _SWUBeginPlayCardUnitPath).
};
