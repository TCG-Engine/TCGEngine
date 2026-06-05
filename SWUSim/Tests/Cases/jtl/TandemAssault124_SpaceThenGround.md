# JTL_124 Tandem Assault — Attack with a space unit, then a ground unit (+2/+0). SOR_237 (space, 2) hits
# the enemy space unit for 2; the chained ground attacker SOR_063 (2+2) hits the enemy ground unit for 4.

## GIVEN
P1LeaderBase: JTL_007/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_124
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_063:1:0
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:4
