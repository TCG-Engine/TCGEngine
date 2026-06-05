# JTL_125 Air Superiority — If you control more space units than an opponent, deal 4 damage to a ground
# unit that opponent controls. P1 has a space unit (P2 none), so it deals 4 to SOR_046.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_125
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
