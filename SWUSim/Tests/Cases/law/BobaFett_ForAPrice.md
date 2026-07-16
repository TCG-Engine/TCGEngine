# OnAttackPayDeal3
#// LAW_214 Boba Fett (6/5) — When Played/On Attack: you may pay 1 resource. If you do, deal 3 damage to
#// a ground unit. Attacks the base; pay 1 -> deal 3 to the enemy SOR_046.

## GIVEN
CommonSetup: yyk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
