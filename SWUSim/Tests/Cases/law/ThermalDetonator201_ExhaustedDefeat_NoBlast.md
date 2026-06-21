# LAW_201 Thermal Detonator — guard: if the host was EXHAUSTED when defeated, the granted When Defeated
# does NOT fire. P1's host (SEC_080 + detonator, EXHAUSTED) is killed by SOR_039; no enemy damage, so
# both P2 ground units survive.

## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
