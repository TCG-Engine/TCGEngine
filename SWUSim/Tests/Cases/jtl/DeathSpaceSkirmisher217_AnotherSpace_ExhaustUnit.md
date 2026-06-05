# JTL_217 Death Space Skirmisher — When Played: If you control another space unit, you may exhaust a
# unit. With another space unit (SOR_237) in play, P1 exhausts the enemy SOR_095.

## GIVEN
P1LeaderBase: JTL_016/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_217
WithP1Resources: 3
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
