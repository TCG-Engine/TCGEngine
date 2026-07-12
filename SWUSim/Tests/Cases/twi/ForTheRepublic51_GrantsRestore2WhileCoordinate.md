# TWI_051 For The Republic — "Attached unit gains: 'Coordinate - Restore 2.'" With 3 friendly units
# (Coordinate active), the host clone (with TWI_051 attached) heals 2 from its own base when it
# attacks. P1 base pre-damaged to 5 → after the attack it's healed to 3. Host power = 2 (clone) + 2
# (TWI_051) = 4 → P2 base takes 4.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArenaUpgrade: 0:TWI_051

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1BASEDMG:3
