# JTL_169 Shadow Caster — When a friendly unit is defeated: you may use all of its
# "When Defeated" abilities again.
# JTL_087 dies attacking SOR_044 → its When Defeated creates a TIE (use #1); Shadow Caster
# lets P1 use it again → a 2nd TIE (use #2). Arena = Shadow Caster + 2 TIEs = 3.

## GIVEN
P1LeaderBase: SOR_002/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP1SpaceArena: JTL_169:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:3
