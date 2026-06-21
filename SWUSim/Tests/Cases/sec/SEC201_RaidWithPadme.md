# SEC_201 Anakin Skywalker (Ground, 3/4) — Hidden + "While you control Padmé Amidala (as a leader or
#   unit), this unit gains Raid 2." With a Padmé Amidala unit (TWI_192) in play, SEC_201 attacks P2's
#   base for 3+2 = 5.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_201:1:0
WithP1GroundArena: TWI_192:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
