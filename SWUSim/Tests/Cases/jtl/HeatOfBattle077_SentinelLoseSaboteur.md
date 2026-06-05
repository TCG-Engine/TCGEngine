# JTL_077 In the Heat of Battle — Each unit gains Sentinel and loses Saboteur for this phase. The
# Saboteur unit SHD_147 gains Sentinel and loses Saboteur.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_077
WithP1Resources: 6
WithP1GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
