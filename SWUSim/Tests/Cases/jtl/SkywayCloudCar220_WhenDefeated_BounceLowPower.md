# JTL_220 Skyway Cloud Car — When Defeated: may return a non-leader unit with 2 or less power to its
# owner's hand. JTL_220 (pre-damaged to 1) attacks SOR_046 and dies to the counter; its When Defeated
# returns the power-2 SOR_225 to P2's hand.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_220:1:2
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
