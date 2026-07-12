# TWI_116 Clone — the copy "is not unique." P1 already controls a real SOR_035 (unique). P1 plays Clone
# copying that SOR_035 → P1 now controls TWO SOR_035 units, but because the Clone copy is non-unique the
# uniqueness rule does NOT force a defeat: both survive. (Without the non-unique treatment, one would be
# defeated and the count would be 1.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP1GroundArena: SOR_035:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:1:CARDID:SOR_035
P1GROUNDARENAUNIT:1:HASTRAIT:Clone
