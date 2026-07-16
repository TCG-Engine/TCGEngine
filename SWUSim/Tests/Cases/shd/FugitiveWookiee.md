# Bounty_ExhaustUnit
#// SHD_211 Fugitive Wookiee (2-cost 3/3) — "Bounty — Exhaust a unit." LAW_124 defeats it; P1
#// collects. Two units remain (the exhausted attacker + a ready marine) → real MZCHOOSE; P1 picks
#// the ready marine, which exhausts.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_211:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
