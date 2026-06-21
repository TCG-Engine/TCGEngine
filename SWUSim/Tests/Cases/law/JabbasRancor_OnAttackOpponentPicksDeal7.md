# LAW_216 Jabba's Rancor (7/7, Hidden) — On Attack: an opponent chooses a ground unit they control;
# you may deal 7 damage to that unit. P2 has one ground unit (SOR_046, auto-chosen); deal 7 -> it dies.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_216:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
