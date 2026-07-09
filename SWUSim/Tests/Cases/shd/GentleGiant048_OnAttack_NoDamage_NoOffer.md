# SHD_048 Gentle Giant — with 0 damage on itself, the heal amount is 0, so the On Attack offer is skipped
# entirely (no decision). The damaged friendly unit is left untouched.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_048:1:0
WithP1GroundArena: SOR_046:1:5

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:5
P1NODECISION
