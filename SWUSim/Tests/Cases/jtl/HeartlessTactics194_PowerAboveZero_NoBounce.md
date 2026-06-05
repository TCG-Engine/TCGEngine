# JTL_194 Heartless Tactics (event) — the bounce only applies if the unit has 0 power after the -2/-0.
# SOR_095 (3 power) drops to 1, so it is exhausted but not bounced.

## GIVEN
P1LeaderBase: JTL_015/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_194
WithP1Resources: 2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
