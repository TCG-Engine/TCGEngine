# ReturnUnderworldFromDiscard
#// LAW_261 Street Gang Recruiter (cost 5) — When Played: you may return an Underworld card from your
#// discard pile to your hand. LAW_124 (Underworld) is in the discard -> return it.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;discardCardIds:LAW_124}
WithP1Hand: LAW_261

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0

---

# OppDiscardUnderworldIsNotReachable
#// COVERAGE: control=OppDiscardUnderworldIsNotReachable + OfferIsYourDiscardOnlyWithOppDiscardStocked +
#//           PlayedByP2_ReturnsToP2Hand — "an Underworld card from YOUR discard pile to YOUR hand" must
#//           resolve from the ability CONTROLLER's seat; every fixture stocks the other seat's discard
#//           with the same Underworld cards so a wrong-seat read is visible. Owner ≠ controller is not
#//           constructible here (the Recruiter is only ever played from its controller's own hand), so
#//           the axis is covered by seat-swap · offer=OfferIsYourDiscardOnlyWithOppDiscardStocked (two
#//           Underworld candidates, non-Underworld card excluded, pick left pending) · decline=
#//           DeclineLeavesDiscardIntact · reqboundary=N/A (one When Played decision, nothing re-read).
#//
#// LAW_261 Street Gang Recruiter — P1's discard holds no Underworld card (SOR_095 Rebel/Trooper, SOR_046
#// Rebel/Trooper) while P2's discard holds two (LAW_124 and LAW_231, both Underworld). The ability must
#// find nothing: no decision pends, P1's hand stays empty, and both discard piles are unchanged at 2.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5}
P1OnlyActions: true
WithP1Discard: [SOR_095 SOR_046]
WithP2Discard: [LAW_124 LAW_231]
WithP1Hand: LAW_261

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:2
P2HANDCOUNT:0
P1GROUNDARENACOUNT:1

---

# OfferIsYourDiscardOnlyWithOppDiscardStocked
#// LAW_261 Street Gang Recruiter — both discard piles hold the same two Underworld cards (LAW_124,
#// LAW_231), so only the mzID frame tells them apart; P1's pile also holds a non-Underworld SOR_095 that
#// must be excluded. Exactly P1's two Underworld entries are selectable — four entries would mean the
#// opponent's pile leaked in. Pick left pending so the offer is what is asserted.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5}
P1OnlyActions: true
WithP1Discard: [LAW_124 LAW_231 SOR_095]
WithP2Discard: [LAW_124 LAW_231]
WithP1Hand: LAW_261

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# DeclineLeavesDiscardIntact
#// LAW_261 Street Gang Recruiter — the return is a "you may", so it can be declined even with two legal
#// Underworld cards waiting. Nothing enters P1's hand and P1's discard keeps all three cards; the
#// Recruiter still enters play.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5}
P1OnlyActions: true
WithP1Discard: [LAW_124 LAW_231 SOR_095]
WithP2Discard: [LAW_124 LAW_231]
WithP1Hand: LAW_261

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:3
P2DISCARDCOUNT:2
P1GROUNDARENACOUNT:1

---

# PlayedByP2_ReturnsToP2Hand
#// LAW_261 Street Gang Recruiter played by P2 — "your discard pile"/"your hand" follow the seat that
#// played it. P2 returns LAW_231 out of P2's discard into P2's hand; P1's discard, stocked with the same
#// two Underworld cards, is untouched at 2 and P1's hand stays empty. Every other section of this file
#// runs from P1, so this is the seat-swap witness.

## GIVEN
CommonSetup: bgw/bgw/{theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Discard: [LAW_124 LAW_231]
WithP2Discard: [LAW_124 LAW_231]
WithP2Hand: LAW_261

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myDiscard-1

## EXPECT
P2HANDCOUNT:1
P2HANDCARD:0:LAW_231
P2DISCARDCOUNT:1
P1DISCARDCOUNT:2
P1HANDCOUNT:0
P2GROUNDARENACOUNT:1
