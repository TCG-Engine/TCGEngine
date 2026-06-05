# SWUSim Replay Schema
Change of Heart — steal ground unit (auto-resolve single target)

## GIVEN
P1LeaderBase: SOR_014/SOR_029
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
