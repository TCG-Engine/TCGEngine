# SEC_050 Vigil (Space, 5/9) — "If damage would be dealt to another friendly unit, prevent 1 of that
#   damage." With SEC_050 in play, SOR_046 (3 power) attacks the friendly SEC_041 → it takes 3-1 = 2.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_050:1:0
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
