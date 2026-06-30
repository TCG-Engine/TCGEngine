# SOR_160 Wolffe — control test: WITHOUT Wolffe's lock, the Restore 1 unit heals P1's base normally
# (3 → 2), proving the lock (not a broken Restore) is what blocks it in the other test.

## GIVEN
CommonSetup: rrw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2
