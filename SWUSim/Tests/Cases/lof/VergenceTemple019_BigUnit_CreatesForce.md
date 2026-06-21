# LOF_019 Vergence Temple — "When the regroup phase starts: If you control a unit with 4 or more
# remaining HP, the Force is with you." P1 controls a 3/7 unit (7 remaining HP ≥ 4). P1 passes to end
# the action phase; at regroup start the condition holds → P1 gains the Force.

## GIVEN
P1LeaderBase: SOR_002/LOF_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1HASFORCE
