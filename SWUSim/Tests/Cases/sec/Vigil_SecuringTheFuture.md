# PreventsDamageToOtherFriendly
#// SEC_050 Vigil (Space, 5/9) — "If damage would be dealt to another friendly unit, prevent 1 of that
#//   damage." With SEC_050 in play, SOR_046 (3 power) attacks the friendly SEC_041 → it takes 3-1 = 2.

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

---

# SelfTakesPlusOne
#// SEC_050 Vigil — "If damage would be dealt to this unit by another card, deal that much damage plus 1
#//   instead." SOR_237 (2 power) attacks SEC_050 → SEC_050 takes 2+1 = 3.

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
