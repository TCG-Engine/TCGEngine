# SEC_050 Vigil — "If damage would be dealt to this unit by another card, deal that much damage plus 1
#   instead." SOR_237 (2 power) attacks SEC_050 → SEC_050 takes 2+1 = 3.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_050:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:3
