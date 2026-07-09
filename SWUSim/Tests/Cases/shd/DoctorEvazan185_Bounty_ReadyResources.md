# SHD_185 Doctor Evazan (2-cost 3/3, Shielded) — "Bounty — Ready up to 12 resources." Fixture-placed
# (no entry shield). LAW_124 defeats him; P1 collects: all 5 exhausted resources ready (fungible —
# auto, no choice).

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_185:1:0
WithP1Resources: 5:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:5
P1GROUNDARENAUNIT:0:DAMAGE:3
