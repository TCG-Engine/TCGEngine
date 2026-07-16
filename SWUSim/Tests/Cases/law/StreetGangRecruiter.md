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
