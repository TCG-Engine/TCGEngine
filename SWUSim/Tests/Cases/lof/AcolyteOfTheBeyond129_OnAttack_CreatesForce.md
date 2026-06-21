# LOF_129 Acolyte of the Beyond — "On Attack/When Defeated: The Force is with you." On Attack half: the
# 2/3 Acolyte attacks P2's base → P1 gains the Force.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_129:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2BASEDMG:2
