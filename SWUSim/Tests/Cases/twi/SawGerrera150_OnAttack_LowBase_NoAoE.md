# TWI_150 Saw Gerrera — absence guard: with P1's base at only 10 damage (< 15), the On Attack AoE does
# NOT fire; the enemy ground units are untouched and only combat (4 + Raid 2 = 6) hits the enemy base.

## GIVEN
CommonSetup: rrw/bbw/{myBaseDamage:10}
P1OnlyActions: true
WithP1GroundArena: TWI_150:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:6
