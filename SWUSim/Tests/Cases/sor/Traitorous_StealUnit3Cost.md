# SWUSim Replay Schema
Traitorous — attach to non-leader unit costing 3 or less, take control of it

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP1Hand: SOR_122
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
