# Offer_StagesOnlyTheCredits_NotTheWholeResourceRow
#// Credit-payment PICKER (live bug report, 2026-08-18): "when asked to use Credits to pay for things,
#// they are not always the 'last resources' in the resource pop-up."
#//
#// Root cause: SWUOfferAltPayment used to offer the Credits by their `myResources-N` mzIDs. Resources is
#// declared `Visibility=Self, Mode=All`, and a Self zone belonging to the VIEWER is routed INLINE — so the
#// prompt lit the Credits up in place along the whole resource row and the player had to hunt for them.
#// Worse, Credits are appended when they are CREATED and anything resourced afterwards lands behind them,
#// so they are not even reliably grouped at the end.
#//
#// The fix stages the Credits into TempZone (`Mode=None` → its own card modal) and offers THOSE, so the
#// picker contains the Credits and nothing else. This section is the assertion that pins it: with 3 real
#// resources and 2 Credits the offer must be exactly the two staged entries — never `myResources-3` /
#// `myResources-4`, and never the real resources at all. The decision is deliberately left PENDING so the
#// pool itself can be inspected (answering it would only prove the branch, never the pool).
#// COVERAGE: offer=this section + NonAdjacentCredits_OfferIsIndependentOfZonePosition ·
#//           decline=Decline_KeepsBothCredits_PaysFullCost ·
#//           boundary=OverLongAnswer_CappedAtTheCost (cap N vs the N+1 the client would never send) +
#//                    NonAdjacentCredits_* (staged index 0 vs 1) ·
#//           control=law/LieutenantGorn_IDeserveWorse::StolenCreditSpendableByNewController (a Credit
#//                   whose controller ≠ its owner is staged and spent by the NEW controller) ·
#//           reqboundary=N/A — the index map is written INTO the CUSTOM decision param, so it is
#//                       serialized with the decision by construction; there is no cross-decision global.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_063
WithP1Credits: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Defeat_any_number_of_Credit_tokens_to_pay_1_resource_less_each
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1
P1TEMPZONECOUNT:2

---

# NonAdjacentCredits_OfferIsIndependentOfZonePosition
#// The same offer with the Credits scattered through the resource row instead of sitting at the end:
#// indices 0/2/4 are real resources and 1/3 are Credits. The staged pool must still be exactly the two
#// Credits, addressed 0 and 1 — the picker's addressing is its own, not the zone's.
#// This is the fixture the old inline prompt made unusable, and it is reachable in a real game: a Credit
#// created on turn 2 sits in front of everything resourced on turns 3+.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:2
P1RESCOUNT:3
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1
P1TEMPZONECOUNT:2

---

# NonAdjacentCredits_SecondStagedPickHitsTheRightSlot
#// ⚠ THE SHARP CASE for the index map. Same scattered board: real/CREDIT/real/CREDIT/real. P1 picks the
#// SECOND staged entry (myTempZone-1), which stands for the Credit at resource index 3.
#//
#// Credit tokens are all LAW_T01 and therefore indistinguishable by CardID, so the positional map built
#// at offer time is the ONLY thing that can say which slot a pick meant. The two plausible wrong maps
#// both fail HERE and nowhere else:
#//   • naive identity (myTempZone-K → myResources-K): K=1 is a Credit in this fixture by luck, so this
#//     one is caught by the ordinary end-of-row fixtures instead (index 0 would be a real resource).
#//   • contiguity assumption (firstCreditIndex + K = 1+1 = 2): resource 2 is a REAL resource, the guard
#//     rejects it, prepaid drops to 0 and the full cost 3 is exhausted — RESAVAILABLE 0, CREDITCOUNT 2.
#// Correct behaviour: one Credit defeated, cost 3 paid as 2 → one real resource still ready.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1CREDITCOUNT:1
P1RESCOUNT:3
P1RESAVAILABLE:1
P1TEMPZONECOUNT:0
P1NODECISION

---

# NonAdjacentCredits_BothStagedPicksResolve
#// The multi-pick half of the sharp case: both scattered Credits are defeated in one answer, so cost 3 is
#// paid as 1. Exercises the batch-mark-then-single-cleanup path THROUGH the map — defeating the Credit at
#// resource index 1 reindexes the zone, which would move the second Credit from index 3 to index 2 if the
#// map were re-resolved mid-loop instead of resolved against the snapshot taken at offer time.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:2
P1TEMPZONECOUNT:0
P1NODECISION

---

# Decline_KeepsBothCredits_PaysFullCost
#// The decline branch through the staged picker: `-` on the MZMULTICHOOSE pays the full cost 3 and keeps
#// both Credits. `P1TEMPZONECOUNT:0` is what makes the REFUSAL path load-bearing: if the staging zone were
#// drained only when a pick was actually made, this run would leave two phantom cards behind, and nothing
#// else on the board would show it — TempZone has no slot, so the leak only surfaces in the NEXT popup.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:2
P1RESAVAILABLE:0
P1TEMPZONECOUNT:0
P1NODECISION

---

# SecondPaymentInTheSamePhase_RestagesCleanly
#// Two Credit payments in one action phase. The staged mzIDs are built as `myTempZone-{i}` counting from
#// ZERO, so if the previous payment's entries were still sitting in TempZone the second offer's addresses
#// would collide with them. P1 plays Battlefield Marine (cost 2) paying with one Credit, then plays the
#// second copy — the offer must be exactly ONE staged entry, `myTempZone-0`, not three and not a stale
#// pair. Left pending so both the pool AND the zone are inspectable.
#// ⚠ `P1SELECTABLEEXACT` alone canNOT catch a missed drain: the offer's mzIDs are built from the credit
#// loop's own 0-based index, so stale entries would sit UNDER a correct-looking candidate list while the
#// popup still rendered three cards. `P1TEMPZONECOUNT:1` is the assertion that actually pins it — the
#// staging zone holds exactly the ONE Credit this offer staged.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_095]
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:1
P1DECISIONTOOLTIP:Defeat_any_number_of_Credit_tokens_to_pay_1_resource_less_each
P1SELECTABLEEXACT:myTempZone-0
P1TEMPZONECOUNT:1

---

# OverLongAnswer_CappedAtTheCost
#// CR 1.7.2 — a payment exhausts resources EQUAL TO the cost, so a Credit can never be defeated for
#// nothing. SEC_040 Emergency Powers costs 1, so the cap is 1 even though BOTH Credits are staged and
#// offered (the MZMULTICHOOSE "0|max|" bound is enforced by the client only). The answer deliberately
#// submits both, the way a tampered or replayed client would.
#// This is also the boundary test for the decision-param layout: the map was inserted as $parts[1], so a
#// handler that still read the cap from the wrong slot would parse the map string "0,1" as a cap and
#// happily defeat both Credits for a cost of 1.
#// P1 has ZERO real resources, so the surviving Credit is visible proof rather than an arithmetic
#// inference: exactly one is spent and one remains.

## GIVEN
CommonSetup: bbk/rrk/{myResources:0}
P1OnlyActions: true
WithP1Hand: SEC_040
WithP1Credits: 2
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P1DISCARDCOUNT:1
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1TEMPZONECOUNT:0
P1NODECISION

---

# ConfirmingZeroCredits_StillPlaysTheCard
#// ⚠ REGRESSION GUARD, live bug 2026-08-26. There are TWO ways to decline this picker and they were not
#// equivalent. `-` (covered by Decline_KeepsBothCredits_PaysFullCost above) worked. Confirming the popup
#// with NOTHING selected submits the literal "PASS", which goes STICKY and makes ExecuteStaticMethods
#// skip every following CUSTOM that is not flagged DontSkipOnPass — and the CREDIT_PAY custom is not
#// merely the applier, it is also what drains the staged TempZone and runs the continuation that PLAYS
#// THE CARD.
#//
#// Measured against the unflagged build: the ground arena stayed EMPTY (the play silently vanished, cost
#// unpaid, card gone from hand) and two phantom staged Credits were left behind to poison the next popup.
#//
#// A zero lower bound means PASS is a CHOICE ("pay none"), never a cancellation. This section is the
#// byte-for-byte twin of the `-` decline above, and the pair is the point: if they ever disagree again,
#// one of them goes red.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:2
P1RESAVAILABLE:0
P1TEMPZONECOUNT:0
P1NODECISION
