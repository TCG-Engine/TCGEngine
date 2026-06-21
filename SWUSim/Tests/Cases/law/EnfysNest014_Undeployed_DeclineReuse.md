# LAW_014 Enfys Nest (undeployed) — declining the reuse: nothing is paid and the
# On Attack ability runs only once. On Attack deals 1 + combat 2 → P2 base = 3.
# Leader stays ready, both resources are untouched.

## GIVEN
P1LeaderBase: LAW_014/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1LEADER:READY
P1RESAVAILABLE:2
