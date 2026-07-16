# Bounty_Deal2ToUnit
#// SHD_167 Wanted Insurgents (3-cost 4/4) — "Bounty — Deal 2 damage to a unit." Industrious Team
#// (4/7) defeats it exactly (4 = HP); takes the 4-power counter. P1 collects: the only unit left
#// (LAW_124, single target → auto-resolve) takes 2 more → 6 total, survives on 7 HP.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_167:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:6
