# LAW_057 Benthic "Two Tubes" (3/2) — On Attack: deal 1 damage to an enemy ground unit. Attacks the
# base; deal 1 to the enemy SOR_046.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
