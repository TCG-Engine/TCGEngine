<?php
// HMW_211
// Cost 3 - Tech, I Thought It Was Obvious - [Cunning,Heroism] - Power 3 - HP 5 - Clone
// Text: When this unit is dealt damage and survives: You may exhaust a unit.

// ─── HMW_211 Tech, I Thought It Was Obvious ──────────────────────────────────
// A SELF observer on the shared damage window (_SWUOnUnitDamaged), registered from that funnel below
// its $survived gate — see the hook site in CardDQHandlers.php.
//
// Three decisions worth recording, because none of them is visible from the printed text alone:
//
// 1. "DEALT DAMAGE", NOT "DEALT COMBAT DAMAGE" — so all THREE damage funnels trigger it: combat,
//    ability/effect (SWUDealDamageToUnit) and INDIRECT (which writes ->Damage directly and bypasses the
//    ability funnel entirely, so it fires the observer from its own site). Settled by the official
//    ruling on the identical wording — Jabba the Hutt, Wonderful Human Being, 10/31/2025: "Jabba's
//    ability triggers when a friendly unit is dealt combat damage or non-combat damage." Contrast
//    SHD_250 Tarfful, which prints "dealt COMBAT damage" and passes $isCombat.
//
// 2. THE OFFER IS QUEUED, NOT BUILT INLINE. The observer runs mid-combat, BEFORE CleanupRemovedCards
//    compacts the arenas — and the unit that dealt Tech its damage very often DIES to Tech's counter in
//    that same combat. A pool built now would carry positional mzIDs that go stale before the player
//    answers. The intermediate CUSTOM drains post-cleanup and builds the offer against the live board.
//    Same shape as SEC_143 The Elite Squad's SEC143_OFFER, immediately above it in that funnel.
//
// 3. ONLY READY UNITS ARE OFFERED. Exhausting an already-exhausted unit is a strict no-op, and a
//    zero-effect target must be unselectable (the engine's exhaust-only-ready convention — SEC_015
//    C-3PO, SHD_201 Principled Outlaw, SEC_069). With nothing ready anywhere the offer is skipped
//    outright rather than presented as a choice that can only waste the "may".
//
// The target pool is an unqualified "a unit": no "another" (so Tech itself is legal), no "friendly"/
// "enemy" (so it spans BOTH sides), and no player word — which in Twin Suns means the WHOLE TABLE.
// SWUOfferUnitTarget's default 'any' side routes through SWUAllUnits(team + their), where ZoneSearch's
// `their` fans out across every live opponent; do NOT hand-roll a my/their pair here.

// Queued by the observer so the pool is built AFTER the combat cleanup. No state rides in memory across
// the decision: everything needed is either in the CUSTOM's own Param or re-read from the live board.
$customDQHandlers["HMW_211#0"] = function($player, $parts, $lastDecision) {
    _SWUHmw211Offer(intval($player));
};

function _SWUHmw211Offer(int $player): void
{
    if ($player <= 0)
        return;
    global $playerID;
    $playerID = $player;
    // "a unit" — every unit on the table, both sides, Tech included. Ready only (see note 3).
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'EXHAUST_UNIT',
        'may'          => true,
        'extraFilter'  => fn($o) => intval($o->Status ?? 0) === 1,
        'question'     => 'Exhaust_a_unit?',
        'prompt'       => 'Exhaust_a_unit',
    ]);
}

// The observer itself. Called from _SWUOnUnitDamaged BELOW its $survived gate — the "and survives"
// clause is that gate, so there is deliberately no survival check here.
function _SWUHmw211CheckObserve($obj, int $amount): void
{
    if ($obj === null || $amount <= 0)
        return;
    if (($obj->CardID ?? '') !== 'HMW_211')
        return;
    if (LostAbilities($obj))
        return;                              // this is Tech's OWN ability
    // "YOU may exhaust" resolves for whoever CONTROLS Tech, not whoever owns it — a stolen Tech reacts
    // for the thief.
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0)
        return;
    DecisionQueueController::AddDecision($ctrl, "CUSTOM", "HMW_211#0", 1);
}
