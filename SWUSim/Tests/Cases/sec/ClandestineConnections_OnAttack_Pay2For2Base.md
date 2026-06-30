# SEC_264 Clandestine Connections (Upgrade) — granted "On Attack: you may pay 2 resources → deal 2 to a
#   base." Host SOR_095 attacks P2 base (3 combat); pay 2 → +2 to P2 base = 5; resources spent.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_264

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:6
P1RESAVAILABLE:0
P1NODECISION
