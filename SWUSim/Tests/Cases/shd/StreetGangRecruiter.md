# ReturnUnderworldFromDiscard
#// SHD_260 Street Gang Recruiter (5-cost ground) — "When Played: You may return an Underworld card from your
#// discard pile to your hand." The Underworld LAW_124 is returned from P1's discard to hand.
#// COVERAGE: offer=N/A (each section seeds exactly one discard card, so the Underworld filter is
#//           asserted by which of the two sections raises a decision at all) · decline=KNOWN-OPEN (the
#//           "you may" pass branch is not asserted in this file) · control=N/A ("from YOUR
#//           discard pile to YOUR hand" is seat-scoped by construction) ·
#//           boundary=ReturnUnderworldFromDiscard (an Underworld card in discard → returned) vs
#//           NoUnderworldInDiscard_NoPrompt (only a non-Underworld card → no decision, discard intact) ·
#//           reqboundary=N/A (a single pick with no state read after it)

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;discardCardIds:LAW_124}
P1OnlyActions: true
WithP1Hand: SHD_260

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0

---

# NoUnderworldInDiscard_NoPrompt
#// SHD_260 Street Gang Recruiter — the return is filtered to UNDERWORLD cards. With only a non-Underworld
#// card (SOR_095, Rebel/Trooper) in P1's discard the When Played has no legal pick, so no decision is
#// raised and the discard is left alone.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1Hand: SHD_260

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
