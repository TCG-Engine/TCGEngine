# LOF_005 Morgan Elsbeth — Action [Exhaust]: Choose a friendly unit that attacked this phase; play a unit
# from your hand that shares a keyword with it, for 1 less. LOF_132 (Raid) attacks the base; then Morgan
# plays LOF_131 (also Raid; cost 2 + 2 off-aspect − 1 discount = 3) from hand — affordable only with the
# discount.

## GIVEN
P1LeaderBase: LOF_005/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1Hand: LOF_131
WithP1Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:0
