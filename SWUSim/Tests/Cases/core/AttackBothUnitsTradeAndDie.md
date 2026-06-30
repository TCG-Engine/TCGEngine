## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
# Both Battlefield Marines are 3/3 — mutual lethal damage
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
