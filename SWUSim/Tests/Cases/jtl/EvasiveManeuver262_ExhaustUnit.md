# JTL_262 Evasive Maneuver (event) — Exhaust a unit. P1 exhausts the enemy SOR_095.

## GIVEN
P1LeaderBase: JTL_016/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_262
WithP1Resources: 2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
