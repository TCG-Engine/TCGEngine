# LAW_014 Enfys Nest (deployed leader unit) — When you use an "On Attack" ability:
# you may use that ability again (NO resource cost; once each round).
# Enfys is deployed in the ground arena. IBH_006 attacks P2's base in space → On Attack
# deals 1; Enfys lets P1 use it again (free) → 1 more; combat 2 → P2 base = 4.
# No resources are spent (deployed reuse is free).

## GIVEN
P1LeaderBase: LAW_014:1:1:1/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: LAW_014:1:0
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1RESAVAILABLE:2
