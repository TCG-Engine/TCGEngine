# SWUSim Replay Schema
Change of Heart — stolen unit returns to owner at start of regroup phase

## GIVEN
P1LeaderBase: SOR_014/SOR_029
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
