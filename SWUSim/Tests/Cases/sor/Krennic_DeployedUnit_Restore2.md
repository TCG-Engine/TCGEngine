# Krennic leader unit side has Restore 2.
# P1 base starts at 4 damage. Krennic (power 2) attacks P2 base,
# heals 2 from P1 base -> P1 base at 2; P2 base takes 2.

## GIVEN
P1LeaderBase: SOR_001:1:1:1/SOR_024:4
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_001:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2
