# JTL_146 Massassi Tactical Officer — Action [Exhaust]: Attack with a Fighter unit (+2/+0). The Fighter
# SOR_237 (power 2) gets +2 → 4 and hits the enemy base for 4; the officer is exhausted.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_146:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED
