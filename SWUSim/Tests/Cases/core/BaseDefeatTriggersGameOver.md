## GIVEN
# P2 base has 26 of 30 HP as pre-damage. Obi-Wan (4 power) attacks → 26+4=30 >= 30 HP → game over
CommonSetup: grw/ggk/{theirBaseDamage:26}
WithP1GroundArena: SOR_049

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1WIN
