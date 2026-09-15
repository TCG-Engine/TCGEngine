<?php
// HMW_049
// Cost 9 - Greater Sarlacc - [Command,Cunning] - Unit (Ground) 9/8
// Traits: Creature
// Text: Overwhelm
//       While playing this unit, you may defeat any number of ready resources you control.
//       For each resource defeated this way, this unit costs [3 resources] less to play.

// ─── HMW_049 Greater Sarlacc ─────────────────────────────────────────────────
// Overwhelm needs NO code — the generator registered 'HMW_049' => true in $Overwhelm_Cards and the
// keyword has generic coverage under Tests/Cases/keywords/.
//
// The rider is EXPLOIT'S SHAPE, and the nearest built example is HMW_125 The Marauder ("choose any
// number of friendly units, deal 1 to each, costs 1 less each"). Four printed differences:
//   The Marauder                          Greater Sarlacc
//   friendly UNITS                   →    READY RESOURCES
//   "FRIENDLY" (team-wide)           →    "YOU CONTROL" (self only — a teammate's resources are NOT
//                                         fodder, which is why this card earns no Team Suns section)
//   deal 1 damage to each            →    DEFEAT them
//   1 resource less per pick         →    3 resources less per pick
//
// ⚠⚠ THE FODDER IS ALSO THE CURRENCY. Every other member of this family spends something that could
// not have paid the bill; here each pick removes a ready resource from what pays the remainder. With
// R ready and k defeated the total outlay is k + max(0, 9 - 3k):
//     k=0 → 9    k=1 → 7    k=2 → 5    k=3 → 3    k=4 → 4
// so the optimum is THREE, and three ready resources is the minimum board that can play this card at
// all. That table is why neither the glow nor the abort guard can copy The Marauder's arithmetic — its
// formula subtracts the whole pool (damaging units costs no capacity), and doing that here would light
// the card up on two resources and would wave through a pick that leaves the play unpayable.
//
// PLUMBING is Exploit's, via HMW_125/HMW_048: _SWUBeginPlayCardUnitPath owns the offer and this CUSTOM
// resolves it, then hands back to SWUContinuePlayAfterExploit so the alt-payment and the real cost are
// charged in one place.
// ⚠ SAME SCOPE AS EXPLOIT: this lives on the SWUBeginPlayCard path, so a direct-ActivateCard nested play
// (SOR_219 Sneak Attack, play-from-deck effects) skips it exactly as those paths already skip Exploit.
// A documented engine-family gap (see hmw-implement.md), not a per-card choice — when the family is
// fixed, fix it at ONE seam for all of them.

// Legal picks: READY resources in the player's OWN resource row.
// ⚠ Deliberately NOT a team-wide scan — the card says "you control", not "friendly" (contrast HMW_125).
// ⚠ Credit tokens sit in the Resources zone but are NOT resources (CR 3.13), so they are neither
// payment capacity nor fodder here; SWUTotalPaymentCapacity skips them for exactly the same reason, and
// the two lists have to agree or the abort guard below mis-prices the play.
function _SWUHmw049LegalPicks(int $player): array {
    global $playerID;
    $saved = $playerID; $playerID = intval($player);
    $out = [];
    $res = GetResources(intval($player));
    for ($i = 0; $i < count($res); $i++) {
        $r = $res[$i];
        if (!empty($r->removed)) continue;
        if (SWUIsCreditToken($r->CardID ?? '')) continue;   // CR 3.13 — not a resource
        if (intval($r->Status ?? 0) !== 1) continue;        // READY only
        $out[] = "myResources-{$i}";
    }
    $playerID = $saved;
    return $out;
}

// The cheapest TOTAL OUTLAY (resources defeated + payment capacity still owed) across every legal
// number of defeats. This is the card's whole economy, and it is shared by the glow and by the offer
// so the two can never drift apart.
// ⚠ Prices each k through SWUApplyCostHalving the way _SWUPlayIsPayableAtDiscount does — discount
// first, halving second (JTL_105 The Starhawk) — so a card that lights up green is never rejected at
// Pay Costs.
function _SWUHmw049MinOutlay(int $player, int $baseCost, int $readyCount): int {
    $best = SWUApplyCostHalving(intval($player), max(0, $baseCost));   // k = 0
    for ($k = 1; $k <= $readyCount; $k++) {
        $outlay = $k + SWUApplyCostHalving(intval($player), max(0, $baseCost - 3 * $k));
        if ($outlay < $best) $best = $outlay;
    }
    return $best;
}

// Resolve the pick: re-validate server-side against the SAME pool that was offered, defeat the picks,
// then continue the play with the discount folded in.
// $parts = [handMzID, playDiscountSoFar, offeredMax]. (The playing effect's grants — enters ready, a
// marker, a Shield — ride SWU_PENDING_PLAY_GRANTS to the PLAY_CARD dispatch, not this param.)
$customDQHandlers["HMW_049#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $handMz   = $parts[0] ?? '';
    $discount = intval($parts[1] ?? 0);
    // The OFFERED maximum, carried across the request boundary. The schema harness (and a
    // non-conforming client) hands an answer straight to this handler without consulting the decision's
    // {max}, so the cap is only real if the server re-applies it here.
    $offeredMax = intval($parts[2] ?? PHP_INT_MAX);

    $picks = [];
    if ($lastDecision !== null && $lastDecision !== '' && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        // Re-check the filter on the server — the offer's pool is a hint, never the gate.
        $legal = array_flip(_SWUHmw049LegalPicks(intval($player)));
        $seen  = [];
        foreach (explode('&', $lastDecision) as $mz) {
            if ($mz === '' || !isset($legal[$mz]) || isset($seen[$mz])) continue;
            $seen[$mz] = true;
            if (count($picks) >= $offeredMax) break;   // never exceed what was actually offered
            $picks[] = $mz;
        }
    }

    // ⚠ ABORT BEFORE ANYTHING IS DEFEATED if the picks did not reduce the cost far enough. The card
    // GLOWS at its best-case outlay, so a player on three ready resources may legally start the play and
    // then confirm only ONE pick — pricing the Sarlacc at 6 against two survivors, which ActivateCard
    // rejects. Playing a card is atomic and a defeated resource is NOT undoable, so the check must come
    // before the defeats, not as a rollback (the reported "additional cost paid before the payment can
    // fail" shape: dead fodder plus the card still in hand). Guarded by
    // UnderChoose_StillUnaffordable_NothingHappens.
    //
    // The self-consuming half no other card in this family has: $capacityLoss tells the helper that
    // these picks are ABOUT to stop being payment. Without it the gate counts each defeated resource
    // twice — once as a 3-resource discount and again as a resource that can still pay.
    if (!_SWUPlayIsPayableAtDiscount(intval($player), $handMz, $discount + 3 * count($picks),
                                     [], count($picks))) {
        SetFlashMessage("Not enough resources to play this even after the reduction — nothing was defeated.");
        return;
    }

    // "For each resource DEFEATED this way" — count the defeats that actually happened, exactly as
    // Exploit's "for each unit defeated" does and unlike The Marauder's "for each unit CHOSEN".
    // ⚠ Descending index order: SWUDefeatResource marks the object removed and CleanupRemovedCards
    // compacts the zone, so a low index processed first shifts every later pick down onto the wrong
    // card. Same reason SWUDefeatResourcesByMzIDs rsorts; that helper is not used here because it
    // returns nothing and this card has to COUNT the defeats.
    $idxs = [];
    foreach ($picks as $mz) {
        $d = strrpos($mz, '-');
        if ($d !== false) $idxs[] = intval(substr($mz, $d + 1));
    }
    rsort($idxs, SORT_NUMERIC);
    $defeated = 0;
    foreach ($idxs as $i) { if (SWUDefeatResource(intval($player), "myResources-{$i}")) $defeated++; }

    // SWUContinuePlayAfterExploit floors the cost at 0, which is what makes over-defeating harmless
    // rather than negative.
    SWUContinuePlayAfterExploit(intval($player), $handMz, $discount + 3 * $defeated);
    // $playerID intentionally not restored — the continue funnel's callers own the restore
    // (mirrors EXPLOIT_RESOLVE / HMW_125#0 / HMW_048#0 / _SWUBeginPlayCardUnitPath).
};
