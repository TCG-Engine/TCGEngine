<?php
// HMW_051
// Cost 4 - Third Sister - Cycle of Vengeance - [Aggression][Cunning][Villainy] - Unit (Ground) 6/3
// Traits: Force, Imperial, Inquisitor - Unique
// Text: Overwhelm
//       When Played: You may deal 2 damage to a unit. If you do, that unit's controller may deal
//       3 damage to a unit. If they do, that unit's controller may deal 4 damage to a unit.
//
// OVERWHELM needs no code (HMW_051 is already in $Overwhelm_Cards, derived from the card text); the
// section in the test file pins the MEMBERSHIP, which is a literal and can be wrong.
//
// THE WHOLE CARD IS "WHO ACTS NEXT". Each link's actor is THE CONTROLLER OF THE UNIT the previous link
// damaged — not "the opponent". Hit an enemy unit and they swing back; hit your OWN unit and you keep
// the chain and take the next link yourself. Nothing in the text says enemy or opponent, so reaching
// for OtherPlayer() here is right only by coincidence, and only in a 2-player game: in Twin Suns the
// next actor can be seat 3 or 4. Pinned by Link1_TargetsYOUROwnUnit_SoYOUActNext and the two Twin Suns
// sections.
//
// THE CHAIN RIDES THE CONTINUATION PARAM, not memory. Three links = three interactive decisions across
// TWO OR MORE players' queues, so every link resumes in a FRESH request — an in-memory global holding
// "how much is left" would be empty by then and the chain would silently stop one link short. The
// remaining amounts travel as a comma list ("2,3,4" → "3,4" → "4"), which also makes the whole ability
// one handler instead of three near-copies. Pinned by FullChain_AcrossTheRequestBoundary.
//
// "IF YOU DO" IS MEASURED, NEVER ASSUMED. Choosing a target is not dealing damage: a Shield token
// prevents the instance (the shield is defeated instead), and prevention/reduction effects can zero it
// out. In those cases no damage was dealt, so the next link does NOT happen — the documented
// attempt-vs-outcome family. _SWUHmw051DealAndReport samples the target's Damage before and after and
// re-resolves it BY UID afterwards, because the applier may re-index the arena or remove the unit.
// ⚠ A target the damage DEFEATS still counts as damaged and still hands its controller the next link,
//   which is why the controller is captured BEFORE the damage — after it, the object is gone.
//
// ⚠ KNOWN GAP, deliberately not papered over: SEC_101 Queen Amidala and ASH_062 The Mandalorian make
//   damage prevention INTERACTIVE — SWUDealDamageToUnit queues a prompt and returns before applying
//   anything. Measured at that instant the damage has not landed, so this chain stops even if the
//   prevention is later declined and the damage does land. Reachable only when the chosen target is
//   one of those two cards with its prevention condition available. Fixing it needs the chain
//   continuation to run AFTER the deferred prevention resolves, which is a cross-queue ordering
//   problem rather than a card-level one. Raised at review rather than silently shipped.

// Deal $amount to $targetMz on behalf of $actor and report whether damage ACTUALLY landed.
// Re-resolves the target BY UID after the applier runs: a defeat (or any re-index) invalidates the
// mzID, and a decider-framed mzID handed back to a source-framed reader is the documented way this
// class of effect vanishes cross-player.
if (!function_exists('_SWUHmw051DealAndReport')) {
    function _SWUHmw051DealAndReport(int $actor, string $targetMz, int $amount): bool {
        global $playerID;
        $playerID = $actor;
        $obj = GetZoneObject($targetMz);
        if ($obj === null || !empty($obj->removed)) return false;
        $uid    = intval($obj->UniqueID ?? 0);
        $before = intval($obj->Damage ?? 0);
        SWUDealDamageToUnit($targetMz, $amount, $actor);
        $playerID = $actor;                      // the applier may have moved the frame
        $after = SWUFindMzByUID($uid);
        if ($after === null) return true;        // gone from every arena: the damage defeated it
        $o2 = GetZoneObject($after);
        if ($o2 === null || !empty($o2->removed)) return true;
        return intval($o2->Damage ?? 0) > $before;
    }
}

// Offer ONE link to $actor: "you may deal <head> damage to a unit", carrying the rest of the chain.
// "A unit" is unqualified — every unit in play on EVERY side and in BOTH arenas, Third Sister included
// (SWUOfferUnitTarget's default side/arena). SWUOfferUnitTarget returns without queueing anything when
// the pool is empty, so a link with no legal target is never offered as a fizzle-only choice.
if (!function_exists('_SWUHmw051OfferLink')) {
    function _SWUHmw051OfferLink(int $actor, array $chain): void {
        if (empty($chain)) return;
        $amount = intval($chain[0]);
        if ($amount <= 0) return;
        SWUOfferUnitTarget($actor, '', [
            'may'          => true,
            'amount'       => $amount,
            'continuation' => 'HMW_051#LINK|' . implode(',', $chain),
            'question'     => "Deal_{$amount}_damage_to_a_unit?",
            'prompt'       => "Deal_{$amount}_damage_to_a_unit",
        ]);
    }
}

// One handler for all three links. $parts[0] is the remaining chain ("2,3,4" on the first call); the
// head is dealt now and the tail — if the damage landed — is offered to the damaged unit's controller.
$customDQHandlers["HMW_051#LINK"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $actor    = intval($player);
    $playerID = $actor;
    if (SWUDecisionDeclined($lastDecision)) return;      // "you may" — a decline ends the chain here
    $chain = array_values(array_filter(explode(',', (string)($parts[0] ?? '')), fn($v) => $v !== ''));
    if (empty($chain)) return;
    $amount = intval(array_shift($chain));
    if ($amount <= 0) return;

    $target = (string)$lastDecision;
    $obj    = GetZoneObject($target);
    if ($obj === null || !empty($obj->removed)) return;
    // BEFORE the damage: the amount can defeat the target, and "that unit's controller" is still the
    // next actor when it does.
    $nextActor = intval($obj->Controller ?? 0);

    if (!_SWUHmw051DealAndReport($actor, $target, $amount)) return;   // prevented → "if you do" fails
    if (empty($chain) || $nextActor <= 0) return;                     // last link, or no owner to pass to
    if (!IsSeatLive($nextActor)) return;                              // eliminated seat cannot act
    _SWUHmw051OfferLink($nextActor, $chain);
};

$whenPlayedAbilities["HMW_051:0"] = function ($player, $mzID = '') {
    _SWUHmw051OfferLink(intval($player), [2, 3, 4]);
};
