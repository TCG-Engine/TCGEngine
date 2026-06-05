# JTL_201 Ahsoka Tano — When Played: An opponent discards a card; if it's a unit, you may exhaust a unit.
# P2's only card (the unit SOR_095) is discarded, so P1 exhausts P2's SOR_046.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
