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
// ★ "IF YOU DO" IS THE CHOICE, NOT THE DAMAGE LANDING — JUDGE RULING 2026-09-14. "If the damage is
// prevented, you still tried to damage it" (CR 9.2; the Malakili ruling). So a link aimed at a SHIELDED
// unit (the Shield takes the hit) or at one whose damage is prevented/reduced to 0 still hands that unit's
// controller the next link. Each link is gated only on the actor ACCEPTING it — a decline ends the chain.
// This file used to measure the target's damage before and after and stop the chain on a prevented hit;
// that was the overturned "measure the outcome" reading. (The prevented damage still fires no "when dealt
// damage" reaction — CR 8.9, enforced centrally in _SWUOnUnitDamaged.)
// ⚠ A target the damage DEFEATS still hands its controller the next link, which is why the controller is
//   captured BEFORE the damage — after it, the object is gone.
// Dropping the measurement also closed the old gap with SEC_101 Queen Amidala / ASH_062 The Mandalorian:
// their INTERACTIVE prevention defers the damage behind a prompt, which a same-instant measurement read as
// "no damage" and stopped the chain. The next link is now queued behind that prompt (same block, later).

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
// head is dealt now and the tail is offered to the targeted unit's controller — prevented or not.
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

    SWUDealDamageToUnit($target, $amount, $actor);   // prevented or not, "If you do" is satisfied
    $playerID = $actor;                               // the applier may have moved the frame
    if (empty($chain) || $nextActor <= 0) return;     // last link, or no owner to pass to
    if (!IsSeatLive($nextActor)) return;                              // eliminated seat cannot act
    _SWUHmw051OfferLink($nextActor, $chain);
};

$whenPlayedAbilities["HMW_051:0"] = function ($player, $mzID = '') {
    _SWUHmw051OfferLink(intval($player), [2, 3, 4]);
};
