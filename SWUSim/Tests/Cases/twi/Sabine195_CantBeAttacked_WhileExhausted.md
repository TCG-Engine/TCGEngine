# TWI_195 Sabine Wren (Unit 4/4, Ground) — "While this unit is exhausted, she can't be attacked
# (unless she gains Sentinel)." Sabine is exhausted (Status 0); P2's SOR_046 tries to attack her → the
# attack is blocked and she survives undamaged.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: TWI_195:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
