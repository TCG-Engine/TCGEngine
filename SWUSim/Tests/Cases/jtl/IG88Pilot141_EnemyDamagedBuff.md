# JTL_141 IG-88 — While an enemy unit is damaged, this unit gets +3/+0. With P2's SOR_046 damaged,
# IG-88 (base power 4) has power 7.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_141:1:0
WithP2GroundArena: SOR_046:1:3

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
