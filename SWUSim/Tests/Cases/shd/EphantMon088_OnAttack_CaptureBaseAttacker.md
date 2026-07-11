# SHD_088 Ephant Mon (5-cost, Command/Villainy ground) — "On Attack: Choose an enemy non-leader unit that
# attacked your base this phase. A friendly unit in the same arena captures that unit." P2's SEC_080 attacks
# P1's base; then Ephant Mon attacks and P1 has the friendly SOR_095 capture SEC_080.

## GIVEN
CommonSetup: ggk/ggk
WithActivePlayer: 2
WithP1GroundArena: SHD_088:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
