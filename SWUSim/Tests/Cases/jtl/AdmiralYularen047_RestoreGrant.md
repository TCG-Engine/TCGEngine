# JTL_047 Admiral Yularen — When Played: choose a keyword; friendly Vehicles gain it. Choosing Restore 1,
# the friendly Vehicle SOR_237 attacks the base (for 2) and heals P1's base by 1 (3 → 2).

## GIVEN
P1LeaderBase: JTL_001/SOR_020:3
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Restore_1
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:2
