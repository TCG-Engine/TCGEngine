<?php
// IC27_158
// Cost 4 - Millennium Falcon - YA-HOO! - [Cunning,Heroism] - Unit (Space) 4/4 (unique)
//   Traits: Rebel, Vehicle, Transport
// Text: When Attack Ends: You may pay [1 resource]. If you do, return a friendly unit that costs 3 or
//       less to its owner's hand. If it's returned to your hand, you may play it for free.
//
// Three chained decisions: pay? -> which unit -> replay it? Nothing is carried in a transient global
// across them — the chosen unit rides $lastDecision and the returned card's hand mzID rides the next
// CUSTOM's own Param, so every hop survives the request boundary (the JTL_094 Luke bug class).
//
// ⚠ The two clauses read DIFFERENT properties: the return is to the unit's OWNER's hand, but the free
// replay is gated on "if it's returned to YOUR hand". They diverge whenever you control a unit the
// opponent owns — then it goes to their hand and you get no replay.

// Friendly units this can return: cost 3 or less, both arenas. Deployed leaders are excluded — they
// cannot be returned to hand at all (SWUBounceUnit refuses), so offering one would be an unresolvable
// choice. The Falcon itself costs 4, so it never appears here.
function Ic27158EligibleReturns(int $player): array {
    global $playerID; $playerID = intval($player);
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval(CardCost($o->CardID ?? '')) <= 3) $out[] = $mz;
        }
    }
    return $out;
}

$onAttackEndAbilities["IC27_158:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Gate on TOTAL payment capacity (ready resources + Credit tokens + SEC_122 Droids), never a bare
    // ready-resource count — a player holding only a Credit CAN pay and must still be offered this.
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    // No legal return target => paying could only waste a resource, so don't raise the offer at all.
    if (empty(Ic27158EligibleReturns(intval($player)))) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        tooltip: "Pay_1_to_return_a_friendly_unit_costing_3_or_less?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'IC27_158#0', 1);
};

$customDQHandlers["IC27_158#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    // Route through the shared alt-payment funnel so Credits / Droids can cover the 1; the
    // IC27_158_PAY continuation pays whatever remains and then offers the return.
    SWUOfferAltPayment(intval($player), 1, 'IC27_158_PAY', '', 1);
};

// The return itself is MANDATORY once the payment is made ("If you do, return …"); only the replay
// that follows is a second "may".
$customDQHandlers["IC27_158#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $owner = intval($obj->Owner ?? $player);
    if ($owner <= 0) $owner = intval($player);
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;
    // "If it's returned to YOUR hand" — an enemy-owned unit went to the opponent's hand instead, so
    // the acting player gets no replay (and the opponent is not offered one either: the clause is
    // scoped to the Falcon's controller, unlike SHD_207's "its owner may play it").
    if ($owner !== intval($player)) return;
    // SWUBounceUnit APPENDS to the owner's hand, so the returned card is the last entry.
    $idx = count(GetHand($owner)) - 1;
    if ($idx < 0) return;
    DecisionQueueController::AddDecision($owner, 'YESNO', '-', 1,
        tooltip: "Play_the_returned_unit_for_free?");
    DecisionQueueController::AddDecision($owner, 'CUSTOM', "LOF_185#2|myHand-{$idx}", 1);
};
