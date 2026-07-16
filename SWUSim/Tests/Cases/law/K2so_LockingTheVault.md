# OnAttackDealDamagedGround
#// LAW_079 K-2SO (3/5, Ambush) — On Attack: you may deal 3 damage to a damaged ground unit. Attacks the
#// base; hit the pre-damaged enemy SOR_046 (2 -> 5).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_079:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
