# SHD_201 Principled Outlaw (4/4) — "On Attack: You may exhaust a ground unit." Only READY units
# are offered (the engine's exhaust-only-ready convention): P2's ready marine is picked and
# exhausts; the already-exhausted Dark Trooper isn't a target.

## GIVEN
CommonSetup: gyw/gyw
P1OnlyActions: true
WithP1GroundArena: SHD_201:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:EXHAUSTED
