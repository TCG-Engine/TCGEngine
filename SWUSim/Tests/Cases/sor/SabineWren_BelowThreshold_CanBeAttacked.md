# SOR_142 Sabine Wren — boundary: with only 2 aspects among other friendly units (Heroism + Villainy),
# the protection is OFF and Sabine can be attacked normally. P2's SEC_080 (3 power) attacks and
# defeats her (2/3).

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 2
WithP1GroundArena: SOR_142:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
