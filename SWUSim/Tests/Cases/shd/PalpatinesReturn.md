# PlayUnitFromDiscardDiscounted
#// SHD_094 Palpatine's Return (6-cost event) — "Play a unit from your discard pile. It costs 6 less. If it's
#// a Force unit, it costs 8 less instead." The non-Force SEC_080 (cost 3) is played for 3-6 = 0 (free),
#// entering P1's ground.

## GIVEN
CommonSetup: ggk/ggk/{myResources:6;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Hand: SHD_094

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1
