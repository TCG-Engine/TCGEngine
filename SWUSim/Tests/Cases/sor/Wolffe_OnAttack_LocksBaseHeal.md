# SOR_160 Wolffe — the lock also triggers On Attack. Wolffe (in play) attacks the enemy base, setting
# the lock; then the Restore 1 unit (SOR_044) attacks and its Restore heal is blocked (base stays 3).
# Base takes Wolffe's 3 + SOR_044's 2 = 5.

## GIVEN
CommonSetup: rrw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:5
