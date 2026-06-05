# JTL_145 BB-8 (pilot) — When played as an upgrade: you may pay 2 resources; if you do, ready a
# Resistance unit. Played onto SOR_237, P1 pays 2 and readies the exhausted Resistance unit JTL_109.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_145
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: JTL_109:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:READY
