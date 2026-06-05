# JTL_235 Commandeer — Take control of a non-leader Vehicle costing 6 or less without a Pilot; ready it.
# P1 commandeers P2's exhausted SOR_237 (cost 2 Vehicle): it moves to P1's arena, ready.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_235
WithP1Resources: 13
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:READY
