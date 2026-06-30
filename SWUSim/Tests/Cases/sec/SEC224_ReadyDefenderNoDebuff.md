# SEC_224 — the -2/-0 applies only to EXHAUSTED enemy defenders. A READY SOR_046 (3/7) counters at its
#   full 3 power, so SEC_224 takes 3 (its 6 lands but SOR_046 survives at 7 HP).

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_224:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:6
