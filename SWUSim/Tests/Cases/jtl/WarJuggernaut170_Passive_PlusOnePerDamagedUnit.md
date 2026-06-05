# JTL_170 War Juggernaut — passive: This unit gets +1/+0 for each damaged unit. With two damaged units
# in play (SOR_095 and SOR_046), the undamaged Juggernaut (printed 3 power) is at 3+2=5.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_170:1:0
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_170
P1GROUNDARENAUNIT:0:POWER:5
