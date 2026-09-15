<?php
// HMW_204
// Cost 7 - Nightbrother - Maul's Gauntlet - [Cunning,Villainy] - Unit (Space) 6/7 - Traits: Vehicle, Transport - Unique
// Text: When Played: You may play a unit from your discard pile. It costs [3 resources] less and enters
//       play ready. At the start of the next regroup phase, defeat it.
//
// TWI_189 Unnatural Life is the near-exact precedent — same three riders (discount, enters ready,
// defeated at the regroup) — differing only in being MANDATORY, restricted to units defeated THIS
// phase, and discounting 2. "The NEXT regroup phase" names the same window TWI_189 and SOR_219 call
// "the regroup phase"; "next" is clarifying, not a second one.
//
// The offer is SWUOfferDiscountPlay over myDiscard, which matters for one reason worth stating: its
// candidate list is built by SWUPlayablesAtDiscount, which prices each card through the SAME pipeline
// that will charge the play (SWUComputePlayCost minus the discount, incl. the aspect penalty) and
// measures it against SWUTotalPaymentCapacity — ready resources PLUS Credits and SEC_122 Droids. A
// hand-rolled `CardCost - 3 <= readyResources` estimate drifts the moment any other cost modifier is in
// play, and it also under-offers to a player who could pay with tokens.
//
// That filter is also what keeps this from becoming a FIZZLE-ONLY OPTIONAL: with nothing in the discard
// affordable at -3 there is no legal target, so no prompt is raised at all. Accepting an offer that can
// only fizzle would burn the "you may" for nothing (the LAW_257 shape).
//
// 'may' => true is the printed "You may": MZMAYCHOOSE, so the offer is presented even with a single
// legal target and the player can always decline. (The hidden-zone rule that forces this on every
// play-from-HAND offer does not apply to a discard pile — it is public — but the card says "may", so
// the answer is the same.)
//
// 'afterAction' => false because this is a UNIT's When Played, not an action: the entry-trigger flush
// owns the after-action, and letting the helper add its own on the empty-pool path double-advances the
// turn.
// ⚠ ONE discount, TWO consumers: it decides which discard cards are AFFORDABLE (the offer filter) and
// what the play is actually CHARGED (the continuation). Those must never be able to disagree — a filter
// that prices a different reduction than the pay path either hides legal picks or offers picks that
// then fizzle. So the number is written once here and RIDES THE CONTINUATION'S PARAM, which also
// survives the request boundary that sits between the offer and its resolution.
$whenPlayedAbilities["HMW_204:0"] = function($player, $mzID = '') {
    $discount = 3;
    SWUOfferDiscountPlay(intval($player), [
        'discount'     => $discount,
        'zone'         => 'myDiscard',
        'types'        => AnyUnitFilter,
        'may'          => true,
        'afterAction'  => false,
        'continuation' => 'HMW_204#0|' . $discount,
        'question'     => "Play_a_unit_from_your_discard_for_3_less?",
        'prompt'       => "Play_a_unit_from_your_discard_(-3,_enters_ready,_defeated_at_regroup)",
    ]);
};

// The chosen discard unit is played through the FULL play ceremony (SWUNestedPlayUnit ->
// SWUBeginPlayCard), which is what makes its own When Played fire AND what charges its additional costs
// — HMW_048 Vernestra Rwoh's "bottom up to 2 units from your discard", Exploit. The earlier SWUNestedPlay
// entered at ActivateCard, the second half of the play, and skipped them: Nightbrother -> Vernestra
// offered no cost and she gained nothing (reported 2026-09-14).
//
// Both riders are play GRANTS rather than stamps applied after the call returns: an additional cost can
// make the player choose, and then the unit arrives in a LATER request, after this handler is long gone.
// The grants ride SWU_PENDING_PLAY_GRANTS to wherever the play finishes. "Enters play ready" is now
// literal, too — the unit is placed ready, instead of being placed exhausted and readied afterwards.
//
// SWU_SNEAK_DEFEAT is SOR_219 Sneak Attack's marker, swept by the RegroupPhaseStart drain loop that
// defeats every unit still carrying it. It is deliberately NOT in $turnEffectRegistry: an unregistered
// token is skipped by SWUExpireTurnEffects, which is exactly the permanence this rider needs to survive
// until the regroup. (The cost is that it shows no source-card provenance in the Active Effects popup —
// a pre-existing gap shared with SOR_219, TWI_189 and SHD_226, not one to fix from here.)
//
// Nested play: Nightbrother's own play already owns this action's ending. A synchronous inner play's
// close is refused by the nested frame; a play that resumes later closes at most once, because the
// ledger refuses whichever close comes second. Guarded by TrapFieldReactsToTheReplayedUnit_StillNoExtraAction
// and, for the deferred leg, by the Vernestra file's ViaNightbrother_*_ExactlyOneTurnSwap section.
$customDQHandlers["HMW_204#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || !preg_match('/myDiscard-(\d+)/', (string) $lastDecision, $m)) return;  // '-' = declined
    global $playerID;
    $playerID = intval($player);
    SWUNestedPlayUnit(intval($player), strval($lastDecision), intval($parts[0] ?? 0),
        ['enterReady' => true, 'turnEffect' => 'SWU_SNEAK_DEFEAT']);
};
