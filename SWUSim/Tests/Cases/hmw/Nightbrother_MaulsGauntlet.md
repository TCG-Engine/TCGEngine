# PlaysAUnitFromDiscard_AtThreeLess_EnteringReady
#// COVERAGE: offer=Offer_OnlyAffordableUnits_EventsAndTooExpensiveAreExcluded
#//           decline=Decline_NothingIsPlayed_NoResourcesSpent
#//                   (+ the DISTINCT cannot-pay branch: UnaffordableEvenAtThreeLess_IsNotOffered)
#//           boundary=CostFloorsAtZero_ACheapUnitIsFree + the two cost points below (6->3 and 4->1)
#//           control=N/A (the only seat-relative wording is "your discard pile", read once when this
#//                   unit's When Played resolves; a later control change cannot re-fire it, and
#//                   Nightbrother has no other in-play ability)
#//           reqboundary=SimulateRequestBoundary_ThePlayStillResolves
#//           modes=2P only ("your discard pile" is self-scoped — no player reference and no
#//                 friendly/enemy wording, so all three formats take the same code path)
#//
#// HMW_204 Nightbrother - Maul's Gauntlet (SPACE, Cunning+Villainy, Vehicle/Transport, unique, 7, 6/7)
#//   "When Played: You may play a unit from your discard pile. It costs [3 resources] less and enters
#//    play ready. At the start of the next regroup phase, defeat it."
#//
#// TWI_189 Unnatural Life is the near-exact precedent — same three riders (discount, enters ready,
#// defeated at regroup), differing only in being MANDATORY, restricted to units defeated THIS phase,
#// and discounting 2. "The NEXT regroup phase" and TWI_189/SOR_219's "the regroup phase" name the same
#// window; "next" is clarifying, not a second one.
#//
#// Nightbrother is a SPACE unit and every discard fixture here is GROUND, so the two never share an
#// arena index and a mis-resolved target cannot hide behind an ambiguous slot.
#//
#// P1 pays 7 for Nightbrother out of 12 (Cunning base + Cunning/Villainy leader covers both pips, so no
#// aspect penalty), leaving 5; LOF_236 Army of the Dead is a vanilla 6-cost Villainy 7/6 that costs 3
#// here, leaving 2. That 2 is the discount assertion: at "2 less" it would be 1, at "4 less" 3.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: LOF_236
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_204
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_236
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:0
P1RESAVAILABLE:2
P1NODECISION

---

# TheDiscountIsThreeAtASecondCostPoint
#// One cost point pins a discount only loosely; two pin it exactly. ASH_242 Death Trooper Squad is a
#// vanilla 4-cost Villainy 5/4, so it costs 1 here and leaves 4 of the 5 resources that survive
#// Nightbrother. Together with the 6->3 case above this rules out every constant other than 3, and
#// rules out a percentage or a "costs 1" flat rewrite.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: ASH_242
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_242
P1RESAVAILABLE:4

---

# CostFloorsAtZero_ACheapUnitIsFree
#// The reduction cannot go negative and hand resources BACK. SOR_247 Underworld Thug is a vanilla
#// 2-cost neutral unit, so 2 - 3 must floor at 0 rather than refunding 1: the five resources left after
#// Nightbrother stay five.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: SOR_247
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_247
P1RESAVAILABLE:5

---

# EntersReady_TheUnitAttacksTheSameTurn
#// "Enters play ready" in its strongest observable form. Exhaustion is the ONLY thing that stops a
#// just-played unit attacking, so a unit that enters ready can swing immediately — which is the entire
#// point of the rider and is not proven by a READY status assertion alone (a status set and then
#// overwritten by the entry path would still read READY at EXPECT time but never get to act).
#// LOF_236 is a 7/6, so P2's base takes exactly 7.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: LOF_236
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:CARDID:LOF_236
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Decline_NothingIsPlayed_NoResourcesSpent
#// The printed "You may" branch. With an affordable unit sitting in the discard the offer must still be
#// refusable — and refusing must cost nothing beyond Nightbrother's own 7, leaving the discard intact.
#// (`-` declines an MZMAYCHOOSE; `NO` is for a YESNO.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: ASH_242
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:ASH_242
P1RESAVAILABLE:5
P1NODECISION

---

# EmptyDiscard_NoPromptAtAll
#// No legal target, so the clause must no-op cleanly rather than raise a prompt with nothing in it.
#// Nightbrother himself still enters play — the "you may play a unit" clause failing does not undo the
#// card that carried it.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_204
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:5
P1NODECISION

---

# UnaffordableEvenAtThreeLess_IsNotOffered
#// CANNOT-PAY IS A DIFFERENT BRANCH FROM DECLINE, and this is the branch that matters most here: an
#// optional effect whose only outcome is a fizzle must not be offered at all (accepting it would burn
#// the choice and do nothing).
#//
#// P1 starts on 9, so 2 remain after Nightbrother. LOF_236 costs 3 even after the reduction — one more
#// than P1 can pay — so there is no legal target and no prompt appears.

## GIVEN
CommonSetup: yyk/bbw/{myResources:9}
P1OnlyActions: true
WithP1Discard: LOF_236
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:2
P1NODECISION

---

# Offer_OnlyAffordableUnits_EventsAndTooExpensiveAreExcluded
#// THE OFFER CELL, with two independent exclusion reasons on one board so neither can cover for the
#// other. P1 has 2 resources left after Nightbrother, and the discard holds:
#//   • ASH_242 (4 -> 1)  legal
#//   • ASH_261 (3 -> 0)  legal          <- two legal targets, so the pool survives to be inspected
#//   • LOF_236 (6 -> 3)  EXCLUDED, one resource beyond reach even at the discount
#//   • SOR_251 Confiscate — an EVENT, EXCLUDED by "a UNIT from your discard pile"
#// ⚠ The affordability filter has to price through the same pipeline that will charge the play
#// (SWUComputePlayCost minus the discount, against total payment capacity), or a hand-rolled estimate
#// drifts the moment any other cost modifier is in play.

## GIVEN
CommonSetup: yyk/bbw/{myResources:9}
P1OnlyActions: true
WithP1Discard: [ASH_242 ASH_261 LOF_236 SOR_251]
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# DefeatedAtTheStartOfTheNextRegroupPhase
#// The delayed-defeat rider. The unit is marked when it enters and the RegroupPhaseStart sweep defeats
#// it, so it leaves the board and lands back in the discard it came from.
#// Nightbrother himself is NOT marked and survives in the space arena — the rider attaches to the unit
#// that was played, not to the card that played it.
#// ⚠ The attack is the RECEIPT, not decoration: "LOF_236 is in the discard" is equally true if the
#// play never happened, and resources cannot pin it either because the regroup READIES them all. Only
#// P2BASEDMG:7 proves the unit really was played, really entered ready, and really acted before the
#// sweep removed it.
#// ⚠ Both decks are seeded: an empty deck at regroup puts CR 6.1 damage on the base, which is exactly
#// the kind of stray number that has moved an assertion in a section like this before.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: LOF_236
WithP1Hand: HMW_204
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:7
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_204
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LOF_236

---

# OnlyThePlayedUnitIsDefeated_BystandersSurvive
#// The NEGATIVE for the delayed defeat: the marker is per-unit, not a board sweep. A friendly SEC_080
#// that was already in the ground arena must still be standing after the regroup, while the unit played
#// out of the discard is gone.
#// Without this, a "defeat every friendly ground unit at regroup" bug passes the section above.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Discard: LOF_236
WithP1Hand: HMW_204
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AttackGroundArena:1:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:7
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1SPACEARENACOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LOF_236

---

# ThePlayedUnitsOwnWhenPlayedStillFires
#// AN ALTERNATE ROUTE INTO PLAY MUST RUN THE FULL CEREMONY. A bespoke "put it in the arena" shortcut
#// seats the unit and silently fires none of its entry triggers, which is invisible against a vanilla
#// fixture — every other section of this file uses one.
#// SOR_111 Patrolling V-Wing is a 2-cost Command 1/1 whose whole text is "When Played: Draw a card",
#// so the draw is the receipt that the canonical play path ran. It costs 4 here (Command is off-aspect
#// under a Cunning base and a Cunning/Villainy leader, +2) and so 1 after the reduction.
#// It is a SPACE unit, so it lands beside Nightbrother at space index 1.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: SOR_111
WithP1Hand: HMW_204
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SOR_111
P1SPACEARENAUNIT:1:READY
P1HANDCOUNT:1
P1DECKCOUNT:2
P1RESAVAILABLE:4

---

# SimulateRequestBoundary_ThePlayStillResolves
#// THE REQUEST-BOUNDARY CELL. The discard choose ends the request in production, so the continuation
#// that plays the card, readies it and marks it for the regroup runs in a FRESH process — anything the
#// offer had parked in an in-memory global would be gone and the handler would return silently, leaving
#// Nightbrother on the board and the discard untouched with the suite still green.
#// Two legal targets so a real decision is pending for the boundary to interrupt.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
P1OnlyActions: true
WithP1Discard: [LOF_236 ASH_242]
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_236
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:ASH_242
P1RESAVAILABLE:2

---

# TurnPassesToTheOpponent_TheNestedPlayDoesNotGrantAnExtraAction
#// ⚠ THIS SECTION EXISTS BECAUSE EVERY OTHER ONE IS BLIND TO IT. They all set P1OnlyActions, which
#// claims initiative and auto-passes the opponent, so the turn returns to P1 whether one after-action
#// ran or two — a DOUBLE SWUAfterAction is literally unobservable there.
#//
#// The nested ActivateCard that plays the discard unit runs its own after-action on top of the one
#// Nightbrother's play already owns. Unneutralised, the turn swaps twice and P1 silently gets a FREE
#// EXTRA ACTION. Found by probing this exact assertion; the 12 sections above were all green against
#// the bug.
#//
#// So: no P1OnlyActions, initiative deliberately unclaimed, one P1 action => the turn must be P2's.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
WithActivePlayer: 1
WithP1Discard: LOF_236
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_236
NOEXTRAACTION
TURNPLAYER:2

---

# TurnPassesToTheOpponent_EvenWhenTheOfferIsDECLINED
#// The control for the section above: declining must also leave exactly one after-action. Without it,
#// a "fix" that simply skips the second swap on the accept path would look complete while the decline
#// path still double-advanced — and the decline path is the one a player takes most often.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
WithActivePlayer: 1
WithP1Discard: LOF_236
WithP1Hand: HMW_204

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
NOEXTRAACTION
TURNPLAYER:2

---

# TrapFieldReactsToTheReplayedUnit_StillNoExtraAction
#// LEG 2 of the nested-play family (plan: SWUSim/docs/action-close-ownership.md).
#// The save/restore only neutralises ActivateCard's IMMEDIATE after-action. When the played unit arms an
#// ENTRY TRIGGER a SWU_TRIGGER_RESUME is queued and finalises LATER, after the restore has run — a
#// second turn swap, i.e. a free extra action. HMW_171 Trap Field is the universal way to reach it: it
#// reacts to ANY non-leader ground unit entering play, either player's, and is owned by the base owner.
#//
#// The two TURNPLAYER sections above CANNOT see this — no trigger fires in their fixtures — which is
#// exactly how it hid on this card until the sweep.
#// P2 holds Trap Field; the replayed LOF_236 trips it, P2 accepts, and the turn must still be P2's.
#// ⚠ P2>Drain first: P1's action leaves P2 holding an undispatched RESOLVE_TRIGGER, and answering at
#// that point cancels the trigger instead of resolving it.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Discard: LOF_236
WithP1Hand: HMW_204
WithP2BaseUpgrade: HMW_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P2>Drain
- P2>AnswerDecision:YES

#// ⚠ NO `NOEXTRAACTION` HERE, deliberately. That assertion means "no second close was ATTEMPTED",
#// and the DEFERRED leg legitimately attempts one: the queued SWU_TRIGGER_RESUME reaches
#// SWUAfterAction after the outer effect already closed the action, and the gate refuses it. The
#// attempt is the mechanism working, not a bug — TURNPLAYER below is what proves no extra action
#// actually happened. Same distinction as docs/action-close-deferrals.md §4: the ledger counts
#// closes PREVENTED, not bugs remaining.

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_236
P1GROUNDARENAUNIT:0:DAMAGE:3
TURNPLAYER:2
