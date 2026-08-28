<?php
// HMW_048
// Cost 6 - Vernestra Rwoh, We Should Handle This Ourselves - [Command][Cunning] - Unit (Ground) 5/5
// Traits: Force, Jedi - Unique (Legendary)
// Text: Sentinel
//       As an additional cost to play this unit, put up to 2 units that each cost 5 or less from your
//       discard pile on the bottom of your deck. This unit gains those units' "When Played" abilities
//       for this phase.
//
// SENTINEL is registry-wired (generic coverage) — no code.
//
// THE ADDITIONAL COST mirrors Exploit's play-path shape: _SWUBeginPlayCardUnitPath owns the offer
// (MZMULTICHOOSE 0..2 over the legal discard picks) and this CUSTOM resolves it, then continues the play
// through the same SWUContinuePlayAfterExploit funnel every unit play uses — so ANY real play path
// (hand, Sneak Attack, discounts) pays the cost without per-path wiring.
//
// RULINGS (2026-08-13):
// - Bottom order is RANDOM ("put on the bottom" names no order) — _topDeckPutRemainingToBottom shuffles.
// - Shielded/Ambush are KEYWORDS, not "When Played" abilities: a donor grants ONLY its registered
//   $whenPlayedAbilities closures. Hardened generically on LOF_197's NoRepeat_Shielded/Ambush sections.
// - The gained abilities ride the TRIGGER BAG (one AddTrigger per donor at her entry collection), so
//   multiple gains order through the normal player-ordering prompt, and each dispatches through
//   OnWhenPlayed — which makes a gained ability count as "using a When Played ability" for LOF_197.
//
// The picked CardIDs cross from the cost step to the entry collection via an SWUVar (NOT a global): the
// continue funnel can raise a Credit/Droid payment decision in between, and a request boundary there
// would empty a global (the JTL_094 family). Collection stamps SWU_HMW048_GAIN_<CID> TurnEffects on her
// for the record + the "for this phase" expiry via the standard phase sweep.

// Legal picks: UNIT cards, printed cost 5 or less, in the caster's own discard.
function _SWUHmw048LegalPicks(int $player): array {
    global $playerID; $saved = $playerID; $playerID = intval($player);
    $out = [];
    foreach (ZoneSearch('myDiscard', null) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $cid = $o->CardID ?? '';
        if (strpos(CardType($cid) ?? '', 'Unit') === false) continue;
        if (intval(CardCost($cid)) > 5) continue;
        $out[] = $mz;
    }
    $playerID = $saved;
    return $out;
}

// Resolve the cost pick: validate server-side, bottom the picks (random order), record the gains,
// restore the caller's consume-once play-grant globals from the snapshot, and continue the play through
// the same funnel every unit play uses. $parts = [handMzID, playDiscount, "ready~grantTE~shield"].
$customDQHandlers["HMW_048#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $handMz   = $parts[0] ?? '';
    $discount = intval($parts[1] ?? 0);
    $snap     = explode('~', (string)($parts[2] ?? ''), 3);

    // ⚠ ABORT BEFORE THE ADDITIONAL COST IS PAID if the play cannot be afforded. Unlike Exploit and
    // HMW_125 this additional cost does NOT reduce the price, so the picks cannot change the answer — but
    // the cards still go to the bottom of the deck BEFORE ActivateCard's payment step, so clicking an
    // unaffordable Vernestra would shuffle two units out of the discard for nothing. The glow is UI only
    // and never gates the click. Same family as HMW_125's UnderChoose_StillUnaffordable_NothingHappens.
    if (!_SWUPlayIsPayableAtDiscount(intval($player), $handMz, $discount)) {
        SetFlashMessage("Not enough resources to play this — nothing was put on the bottom of your deck.");
        return;
    }

    $gained = [];
    if ($lastDecision !== null && $lastDecision !== '' && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        // Re-validate against the SAME pool that was offered (the filter must hold on the server —
        // the top-deck-search lesson), snapshot CardIDs, then remove; cap at 2.
        $legal = array_flip(_SWUHmw048LegalPicks(intval($player)));
        foreach (explode('&', $lastDecision) as $mz) {
            if ($mz === '' || !isset($legal[$mz]) || count($gained) >= 2) continue;
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $gained[] = $o->CardID ?? '';
            $o->removed = true;
        }
        DecisionQueueController::CleanupRemovedCards();
        if (!empty($gained)) {
            _topDeckPutRemainingToBottom(intval($player), $gained);   // shuffles = random bottom order
        }
    }
    SetSWUVar('SWU_HMW048_GAINS', implode(',', $gained));
    // Restore the play-grant globals the original caller had armed (nulled once it returned).
    global $gForceEnterReady, $gPlayGrantTurnEffect, $gPlayGrantShield;
    if (($snap[0] ?? '0') === '1') $gForceEnterReady     = true;
    if (($snap[1] ?? '')  !== '') $gPlayGrantTurnEffect = $snap[1];
    if (intval($snap[2] ?? 0) > 0) $gPlayGrantShield     = intval($snap[2]);
    SWUContinuePlayAfterExploit(intval($player), $handMz, $discount);
    // $playerID intentionally not restored — the continue funnel's callers own the restore
    // (mirrors EXPLOIT_RESOLVE / _SWUBeginPlayCardUnitPath).
};
