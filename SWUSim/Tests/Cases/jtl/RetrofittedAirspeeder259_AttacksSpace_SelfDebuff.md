# JTL_259 Retrofitted Airspeeder — can attack space units (cross-arena), and while attacking a space
# unit it gets -1/-0. The ground Airspeeder (power 3) attacks the space SOR_237 (2/3): reduced to power
# 2, it deals 2; SOR_237's counter (2) damages the Airspeeder.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_259:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:S0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:CARDID:JTL_259
P1GROUNDARENAUNIT:0:DAMAGE:2
