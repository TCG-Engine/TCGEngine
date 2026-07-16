# CombinedPowerExhaust
#// LOF_202 Mind Trick — Exhaust any number of units with a combined power of 4 or less. SOR_059 (power 1)
#// and SOR_063 (power 2) total 3 ≤ 4, so both are exhausted.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
