# JTL_189 Boba Fett (pilot) — When played as an upgrade: you may deal 1 damage to a unit (2 if the
# attached unit is a Transport). Played onto JTL_186 (Transport), it deals 2 to SOR_046.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_189
WithP1SpaceArena: JTL_186:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
