# PowerPerExhaustedFriendly
#// SEC_114 Kino Loy (Ground, 1/5) — "+1/+0 for each other exhausted friendly unit." With two exhausted
#//   friendly units in play, Kino Loy is 3 power → attacks P2's base for 3.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_114:1:0
WithP1GroundArena: SEC_041:0:0
WithP1GroundArena: SEC_042:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P2BASEDMG:3
