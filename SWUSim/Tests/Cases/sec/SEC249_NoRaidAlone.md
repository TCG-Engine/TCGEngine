# SEC_249 — without another Official unit in play, the conditional Raid 2 is off, so it attacks the
#   base for its base 1 power.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_249:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
