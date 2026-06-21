# LAW_155 Getaway Freighter (1/4, space) — On Attack: if you control a ground unit, create a Credit
# token. P1 controls SEC_080 (ground); attacks the base -> 1 Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_155:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
