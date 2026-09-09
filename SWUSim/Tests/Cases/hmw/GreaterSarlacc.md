# DefeatThree_PlaysForFree_AtTheMinimumResourceCount
#// HMW_049 Greater Sarlacc. Ground Unit, cost 9, 9/8, [Command][Cunning].
#// Text: "Overwhelm
#//        While playing this unit, you may defeat any number of ready resources you control.
#//        For each resource defeated this way, this unit costs [3 resources] less to play."
#//
#// COVERAGE: offer=Offer_ReadyResourcesYouControlOnly_ExcludesExhaustedAndEnemy
#//           decline=DefeatNone_DashDecline_FullPrice + ChooseNone_EmptyConfirm_FullPrice
#//           boundary=Glow_AffordableOnlyWithTheReduction / Glow_UnaffordableEvenAtMaxReduction
#//                    (three ready resources is exactly the minimum; two is exactly one short)
#//                    + UnderChoose_TwoOfFour_LeavesTooFewPayers_NothingHappens (the abort guard's own
#//                    boundary: 9→3 looks payable on a four-resource board and is not, because two of
#//                    the four are the fodder)
#//           payment=Glow_CreditsPayTheRemainder_ButAreNotFodder /
#//                    Glow_CreditsCannotSubstituteForFodder_StillDark — a Credit token is capacity but
#//                    never fodder (CR 3.13), and this pair is the ONLY board that separates the
#//                    correct min-over-k glow from HMW_125's subtract-the-whole-pool one
#//           control=N/A (structural — "resources YOU CONTROL" is read from the resource zone's
#//                 controller, and the excluded-enemy half is asserted in the offer section; the
#//                 card moves nothing between zones and writes nothing to another player's board)
#//           reqboundary=RequestBoundary_PicksSurviveIntoTheCostStep
#//           modes=2P only ("you control", not "friendly" — self-scoped in every format. This is
#//                 the deliberate contrast with HMW_125 The Marauder, whose "FRIENDLY units" earns
#//                 a Team Suns section; a teammate's resources are NOT fodder here.)
#//
#// ⚠ THE FODDER IS ALSO THE CURRENCY, which no other card in this family has to reckon with.
#// HMW_125 The Marauder damages units — that costs nothing you could have paid with. Defeating a
#// READY RESOURCE removes it from what can pay the remainder, so the cheapest way to play the
#// Sarlacc is NOT "defeat as many as possible". With R ready resources and k defeated the outlay
#// is k + max(0, 9 - 3k):
#//     k=0 → 9   k=1 → 7   k=2 → 5   k=3 → 3   k=4 → 4
#// so THREE is the optimum and three ready resources is the minimum board that can play this card
#// at all. Every boundary section here is built on that table.
#//
#// This section is the sharpest positive: exactly three ready resources, all three defeated, cost
#// floors at 0, and the player ends with an empty resource row and a 9/8 on the board.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_049
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:8
P1RESCOUNT:0
P1HANDCOUNT:0

---

# DefeatNone_DashDecline_FullPrice
#// The clause is a "you may", so defeating nothing is always legal — the Sarlacc is then simply a
#// 9-cost unit. Nine ready resources, decline with `-`, pay the printed nine.
#//
#// The resources are EXHAUSTED (spent), not defeated: the count stays at 9 and only the available
#// count drops. That distinction is the whole difference between paying and using this ability.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 9

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_049
P1RESCOUNT:9
P1RESAVAILABLE:0

---

# ChooseNone_EmptyConfirm_FullPrice
#// STICKY-PASS GUARD, and it is not a duplicate of the `-` section above. A 0-minimum multi-select
#// confirmed with nothing selected submits the literal "PASS", which goes STICKY —
#// ExecuteStaticMethods then skips every unflagged CUSTOM after it. The continuation this card
#// queues is what PLAYS THE CARD, so a picker built with a raw AddDecision pair instead of
#// SWUQueueMultiChoose makes the Sarlacc VANISH from the game rather than resolve at full price.
#//
#// Byte-for-byte the same expectations as the `-` decline; only the answer differs.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 9

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_049
P1RESCOUNT:9
P1RESAVAILABLE:0

---

# Offer_ReadyResourcesYouControlOnly_ExcludesExhaustedAndEnemy
#// OFFER CELL. The printed restriction is doubly narrow — "READY resources YOU CONTROL" — so the
#// pool must exclude both an exhausted resource of your own and every resource the opponent has.
#// Answering a pick proves neither exclusion; only reading the pool does.
#//
#// Board: four of P1's resources ready, two exhausted, and P2 holding six of their own. A pool that
#// is any wider than the four is a different card.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049;theirResources:6}
P1OnlyActions: true
WithP1Resources: 4:SOR_046:1,2:SOR_046:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-2&myResources-3

---

# DefeatTwo_CostDropsToThree
#// QUANTITY DISCRIMINATION. The rate is 3 per resource, not 1 and not 2 — so two defeated
#// resources take a 9-cost unit to 3. The board is chosen so the arithmetic can only come out one
#// way: six ready, two defeated, three exhausted to pay, leaving exactly one still ready.
#//
#// A rate of 1 would leave a cost of 7 (unpayable from the four survivors, so the play would fail);
#// a rate of 2 would leave 5 and one fewer survivor. Only 3 produces this exact end state.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_049
P1RESCOUNT:4
P1RESAVAILABLE:1

---

# OverDefeat_CostFloorsAtZero_AndTheExtraResourceIsStillGone
#// "ANY NUMBER" is literal: the cap is the ready pool, not the cost. Defeating four when three
#// would have sufficed is legal and simply wasteful — the cost floors at 0 rather than going
#// negative, and the fourth resource is gone all the same.
#//
#// The waste is the assertion: five ready in, four defeated, one survivor, nothing exhausted.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2&myResources-3

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_049
P1RESCOUNT:1
P1RESAVAILABLE:1

---

# Glow_AffordableOnlyWithTheReduction
#// GLOW CELL, low half of the boundary pair. Three ready resources cannot pay a printed 9, so the
#// affordability predicate must model the reduction or the card sits DARK BUT CLICKABLE — the
#// reported "affordable cards in hand aren't highlighted" shape.
#//
#// ⚠ And it cannot reuse HMW_125's glow formula. The Marauder subtracts its whole friendly pool
#// because damaging units costs no capacity; here every subtraction also REMOVES a payer, so the
#// predicate has to minimise k + max(0, cost - 3k) over the ready pool rather than take k at its
#// maximum. At three ready the answer is k=3, outlay 3 — exactly affordable.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 3

## EXPECT
P1HANDGLOW:0

---

# Glow_UnaffordableEvenAtMaxReduction
#// GLOW CELL, high half of the pair. Two ready resources is one short at EVERY k:
#//     k=0 → pay 9 with 2   k=1 → pay 6 with 1   k=2 → pay 3 with 0
#// so the card must stay dark. Without this partner a glow that simply always lights would pass
#// the section above.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 2

## EXPECT
P1HANDGLOWNOT:0

---

# UnderChoose_StillUnaffordable_NothingHappens
#// ABORT-BEFORE-ANYTHING-APPLIED. The card glows at its BEST-CASE reduction, so a player on three
#// ready resources may legally start the play and then confirm only ONE pick — pricing the Sarlacc
#// at 6 against two survivors, which the payment step rejects.
#//
#// Playing a card is atomic, so the resource that pick would have destroyed must NOT stay
#// destroyed. Defeating a resource is not undoable once done, so the check belongs BEFORE the
#// defeats — the same guard HMW_125 needs for its damage, and the same reported bug shape
#// (additional costs paid before the payment can fail: dead fodder plus the card still in hand).

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESCOUNT:3
P1RESAVAILABLE:3

---

# RequestBoundary_PicksSurviveIntoTheCostStep
#// REQUEST-BOUNDARY CELL. The picker ends the request: the Sarlacc's hand mzID, the running
#// discount and the play-grant snapshot are written when the offer is queued and read in the
#// continuation that actually plays the card. Anything parked in an in-memory global between those
#// two points is empty in the fresh process, and the card would neither land nor return.
#//
#// Same GIVEN and same answer as the free-play section — only the boundary line is inserted.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 3

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_049
P1RESCOUNT:0
P1HANDCOUNT:0

---

# UnderChoose_TwoOfFour_LeavesTooFewPayers_NothingHappens
#// THE SELF-CONSUMPTION CELL — the one section HMW_125 The Marauder could never have. Four ready
#// resources, two defeated: the cost drops 9 → 3, which LOOKS payable against a board that started
#// with four. It is not. The two picks are gone, so only TWO payers remain against a 3-cost, and the
#// play must abort with nothing defeated.
#//
#// A payability gate that counts the resources it is about to destroy as still able to pay waves this
#// through: it defeats two, then ActivateCard rejects the play, and the player is left down two
#// resources with the Sarlacc still in hand — the reported "additional cost paid before the payment
#// can fail" shape, in its worst form because a defeated resource cannot be given back.
#//
#// The sibling section above (one of three) aborts for the ordinary reason — 6 > 3 either way — so it
#// passes with the capacity correction deleted. Only this board separates the two.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 4

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESCOUNT:4
P1RESAVAILABLE:4

---

# Glow_CreditsPayTheRemainder_ButAreNotFodder
#// CREDIT-PAYMENT CELL, and the positive half of the pair that actually pins the glow FORMULA.
#//
#// A Credit token sits in the resource row but is NOT a resource (CR 3.13), so it is payment capacity
#// and never fodder. Two ready resources plus three Credits: defeat BOTH resources (cost 9 → 3) and the
#// three Credits cover the remainder exactly — outlay 2 + 3 = 5 against a capacity of 5, so the card
#// must light up.
#//
#// If the glow counted ready resources alone, capacity would read 2 against a best outlay of 5 and the
#// Sarlacc would sit dark but clickable — the reported "affordable cards in hand when I have Credits
#// aren't highlighted" shape, at this card's site.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 2
WithP1Credits: 3

## EXPECT
P1HANDGLOW:0

---

# Glow_CreditsCannotSubstituteForFodder_StillDark
#// ⚠ THE SECTION THAT PINS THE FORMULA ITSELF, and the only one that can. Two ready resources plus ONE
#// Credit — capacity 3.
#//
#// HMW_125 The Marauder's glow line ("subtract the whole pool") prices this at 9 − 3×2 = 3 and lights
#// the card up. It is wrong: defeating both resources leaves ONE Credit against a 3-cost. The honest
#// minimum is 2 + 3 = 5, so the card must stay dark.
#//
#// Every OTHER board separates the two formulas by nothing — at 3 ready resources both say "glow", at
#// 2 with no Credits both say "dark". It takes capacity that is NOT fodder to drive a wedge between
#// them, which is exactly what a Credit is. Measured: with the naive formula in place the whole rest of
#// this file stays green.

## GIVEN
CommonSetup: gyk/rrk/{myhandCardIds:HMW_049}
P1OnlyActions: true
WithP1Resources: 2
WithP1Credits: 1

## EXPECT
P1HANDGLOWNOT:0
