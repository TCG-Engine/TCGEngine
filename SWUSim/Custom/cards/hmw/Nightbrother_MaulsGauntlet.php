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

// The chosen discard unit is played through the CANONICAL path (ActivateCard), which is what makes its
// own When Played fire — a bespoke "drop it in the arena" shortcut would seat the unit and silently run
// none of its entry triggers.
//
// Both riders are then stamped on the unit that actually arrived, located via gLastPlayedMzID rather
// than by re-deriving an index: the arena has just grown, and a positional guess would be a slot ahead
// or behind depending on which arena the played unit belongs to.
//
// SWU_SNEAK_DEFEAT is SOR_219 Sneak Attack's marker, swept by the RegroupPhaseStart drain loop that
// defeats every unit still carrying it. It is deliberately NOT in $turnEffectRegistry: an unregistered
// token is skipped by SWUExpireTurnEffects, which is exactly the permanence this rider needs to survive
// until the regroup. (The cost is that it shows no source-card provenance in the Active Effects popup —
// a pre-existing gap shared with SOR_219, TWI_189 and SHD_226, not one to fix from here.)
$customDQHandlers["HMW_204#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || !preg_match('/myDiscard-(\d+)/', (string) $lastDecision, $m)) return;  // '-' = declined
    global $playerID, $gTurnPlayer;
    $playerID = intval($player);
    // ⚠ ActivateCard runs its OWN after-action, which would swap the turn a SECOND time on top of the
    // one Nightbrother's own play already owns — handing this player a free extra action. Neutralise it
    // with the JTL_089#1 save/restore. This is invisible under P1OnlyActions (the opponent auto-passes,
    // so the turn comes back either way); only a TURNPLAYER assertion on an alternating turn can see it.
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, intval($parts[0] ?? 0));  // discount from the offer
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
    $newMz = $GLOBALS['gLastPlayedMzID'] ?? '';
    if ($newMz === '' || $newMz === null) return;
    $o = GetZoneObject($newMz);
    if ($o !== null) $o->Status = 1;                 // enters play ready
    AddTurnEffect($newMz, 'SWU_SNEAK_DEFEAT');       // defeated at the start of the next regroup phase
};
