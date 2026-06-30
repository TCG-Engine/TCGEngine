# SEC_135 — the protection applies only while READY. An EXHAUSTED SEC_135 (3 HP) can be attacked: P2's
#   SOR_046 (3 power) kills it.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_135:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
