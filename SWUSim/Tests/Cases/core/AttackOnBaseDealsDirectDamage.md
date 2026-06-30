## GIVEN
# Cell Block Guard (3 power) attacks enemy base — no defenders
CommonSetup: grw/ggk
WithP1GroundArena: SOR_229:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1BASEDMG:0
