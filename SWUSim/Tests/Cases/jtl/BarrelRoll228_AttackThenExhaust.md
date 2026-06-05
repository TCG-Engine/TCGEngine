# JTL_228 Barrel Roll — Attack with a space unit; after completing the attack, you may exhaust a space
# unit. SOR_237 hits the enemy base for 2, then P1 exhausts the enemy space unit SOR_044.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_228
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2BASEDMG:2
P2SPACEARENAUNIT:0:EXHAUSTED
