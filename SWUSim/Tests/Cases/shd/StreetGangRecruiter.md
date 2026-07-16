# ReturnUnderworldFromDiscard
#// SHD_260 Street Gang Recruiter (5-cost ground) — "When Played: You may return an Underworld card from your
#// discard pile to your hand." The Underworld LAW_124 is returned from P1's discard to hand.

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
