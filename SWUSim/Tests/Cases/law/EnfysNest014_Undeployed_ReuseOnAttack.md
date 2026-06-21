# LAW_014 Enfys Nest (undeployed leader) — When you use an "On Attack" ability:
# you may pay 2 resources and exhaust this leader; if you do, use that ability again.
# IBH_006 Rebellion Y-Wing (On Attack: deal 1 to a base) attacks P2's base in space.
# On Attack deals 1; Enfys reuse deals 1 more; combat (power 2) deals 2 → P2 base = 4.
# Leader exhausts, the 2 resources are spent.

## GIVEN
P1LeaderBase: LAW_014/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
