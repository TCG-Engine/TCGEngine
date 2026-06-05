# JTL_043 No Glory, Only Results — Take control of a non-leader unit, then defeat it. P1 targets P2's
# SOR_046: it is taken and defeated, landing in its owner (P2)'s discard.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1Resources: 13
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
