# JTL_178 Face Off (event) — If no player has taken the initiative this phase, you may ready an enemy
# unit; if you do, ready a friendly unit in the same arena. With no initiative taken, P1 readies the
# exhausted enemy SOR_237, then readies the friendly SOR_225 (same arena).

## GIVEN
P1LeaderBase: JTL_012/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_178
WithP1Resources: 3
WithP1SpaceArena: SOR_225:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:READY
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:READY
