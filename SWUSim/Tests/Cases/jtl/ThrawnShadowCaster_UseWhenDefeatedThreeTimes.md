# JTL_002 Thrawn (undeployed) + JTL_169 Shadow Caster together — a single "When Defeated"
# ability used THREE times. JTL_087 dies attacking SOR_044:
#   use #1 — original When Defeated (create a TIE)
#   use #2 — Thrawn exhausts to use it again
#   use #3 — Shadow Caster uses it again (reacts to the defeat)
# Arena = Shadow Caster + 3 TIEs = 4.

## GIVEN
P1LeaderBase: JTL_002/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP1SpaceArena: JTL_169:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:4
