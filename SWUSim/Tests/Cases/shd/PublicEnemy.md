# Bounty_ShieldToUnit
#// SHD_068 Public Enemy — attached unit gains "Bounty — Give a Shield token to a unit." P2's
#// Battlefield Marine wears it; Industrious Team (LAW_124 4/7) defeats the marine; P1 collects and
#// the only surviving unit (LAW_124, single target → auto-resolve) gets a Shield token.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_068

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
