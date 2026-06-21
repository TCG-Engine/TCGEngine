# LOF_019 Vergence Temple — negative: P1 controls only a 3/3 unit (3 remaining HP < 4), so the
# regroup-start condition fails and no Force token is created.

## GIVEN
P1LeaderBase: SOR_002/LOF_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1NOFORCE
